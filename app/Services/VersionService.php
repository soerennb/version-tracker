<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Enums\RejectReason;
use App\Enums\ReviewAction;
use App\Enums\VersionStatus;
use App\Models\Version;
use App\Models\VersionReview;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class VersionService
{
    public function __construct(
        protected NotificationService $notificationService,
        protected ReleaseReadinessService $releaseReadinessService,
        protected VersionGovernanceService $versionGovernanceService,
        protected RuntimeSettings $runtimeSettings,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Version
    {
        unset($data['status'], $data['approval_status']);
        $data['created_by'] = Auth::id();

        $version = Version::create($data);

        $version->forceFill([
            'status' => VersionStatus::DRAFT,
            'approval_status' => ApprovalStatus::PENDING,
        ])->save();

        $this->syncSoftwareMetadata($version);

        return $version->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Version $version, array $data): Version
    {
        unset($data['status'], $data['approval_status']);

        $requiresReview = in_array($version->approval_status, [ApprovalStatus::APPROVED, ApprovalStatus::REJECTED], true);
        $previousApprovalStatus = $version->approval_status?->value;

        $version->fill($data);

        if ($requiresReview) {
            $version->forceFill([
                'approval_status' => ApprovalStatus::PENDING,
                'rejection_reason' => null,
            ]);
        }

        $version->save();

        $this->syncSoftwareMetadata($version);

        if ($requiresReview) {
            VersionReview::create([
                'version_id' => $version->id,
                'user_id' => auth()->id(),
                'action' => ReviewAction::COMMENT,
                'comment' => __('versions.governance.approval_invalidated'),
                'metadata' => [
                    'previous_approval_status' => $previousApprovalStatus,
                ],
            ]);
        }

        return $version->refresh();
    }

    public function approve(Version $version, bool $override = false, ?string $overrideReason = null): Version
    {
        if (! $version->status?->isDraft() || ! $version->approval_status?->isPending()) {
            throw ValidationException::withMessages([
                'version' => __('versions.governance.approve_requires_pending'),
            ]);
        }

        $user = Auth::user();

        if ($user) {
            $this->versionGovernanceService->assertCanApprove($user, $version);
        }

        $readiness = $this->releaseReadinessService->evaluate($version);

        if ($override) {
            if (! $this->runtimeSettings->governance()->allow_readiness_override) {
                throw new AuthorizationException(__('versions.governance.override_not_allowed'));
            }

            if (! $user || ! $user->can('override_release_readiness')) {
                throw new AuthorizationException(__('versions.governance.override_not_allowed'));
            }

            if (blank($overrideReason)) {
                throw ValidationException::withMessages([
                    'override_reason' => __('versions.governance.override_reason_required'),
                ]);
            }
        }

        if (! $readiness['is_ready'] && ! $override) {
            throw ValidationException::withMessages([
                'version' => __('versions.governance.not_ready'),
                'readiness' => collect($readiness['blockers'])->pluck('label')->implode(' '),
            ]);
        }

        $version->approval_status = ApprovalStatus::APPROVED;
        $version->save();

        $version = $version->refresh();

        VersionReview::create([
            'version_id' => $version->id,
            'user_id' => auth()->id(),
            'action' => ReviewAction::APPROVED,
            'comment' => $override ? $overrideReason : null,
            'metadata' => [
                'readiness' => $readiness,
                'overridden' => $override,
            ],
        ]);

        $this->notificationService->notifyVersionApproved($version);

        return $version;
    }

    public function publish(Version $version): Version
    {
        if ($version->approval_status !== ApprovalStatus::APPROVED) {
            throw ValidationException::withMessages([
                'version' => __('versions.governance.publish_requires_approval'),
            ]);
        }

        if ($version->status === VersionStatus::PUBLISHED) {
            return $version;
        }

        $version->forceFill([
            'status' => VersionStatus::PUBLISHED,
            'rejection_reason' => null,
        ])->save();

        $this->syncSoftwareMetadata($version);

        $version = $version->refresh();

        VersionReview::create([
            'version_id' => $version->id,
            'user_id' => auth()->id(),
            'action' => ReviewAction::PUBLISHED,
        ]);

        $this->notificationService->notifyVersionPublished($version);

        return $version;
    }

    public function reject(Version $version, ?string $reason = null, RejectReason|string|null $rejectReason = null): Version
    {
        if (! $version->status?->isDraft() || ! $version->approval_status?->isPending()) {
            throw ValidationException::withMessages([
                'version' => __('versions.governance.reject_requires_pending'),
            ]);
        }

        $rejectReason = is_string($rejectReason) ? RejectReason::tryFrom($rejectReason) : $rejectReason;

        $version->approval_status = ApprovalStatus::REJECTED;
        $version->status = VersionStatus::DRAFT;
        $version->rejection_reason = $reason;
        $version->save();

        $version = $version->refresh();

        VersionReview::create([
            'version_id' => $version->id,
            'user_id' => auth()->id(),
            'action' => ReviewAction::REJECTED,
            'reject_reason' => $rejectReason,
            'comment' => $reason,
        ]);

        return $version;
    }

    protected function syncSoftwareMetadata(Version $version): void
    {
        $software = $version->software()->first();

        if (! $software) {
            return;
        }

        $latestPublished = $software->versions()
            ->where('status', VersionStatus::PUBLISHED->value)
            ->orderByDesc('release_date')
            ->first();

        $software->forceFill([
            'current_version' => $latestPublished?->version_number,
            'last_release_date' => $latestPublished?->release_date,
        ])->save();
    }
}
