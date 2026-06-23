<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Enums\RejectReason;
use App\Enums\ReviewAction;
use App\Enums\VersionStatus;
use App\Models\Version;
use App\Models\VersionReview;
use Illuminate\Support\Facades\Auth;

class VersionService
{
    public function __construct(
        protected NotificationService $notificationService,
        protected VersionGovernanceService $versionGovernanceService,
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

        $version->fill($data);
        $version->save();

        $this->syncSoftwareMetadata($version);

        return $version->refresh();
    }

    public function approve(Version $version): Version
    {
        $user = Auth::user();

        if ($user) {
            $this->versionGovernanceService->assertCanApprove($user, $version);
        }

        $version->approval_status = ApprovalStatus::APPROVED;
        $version->status = VersionStatus::PUBLISHED;
        $version->save();

        $this->syncSoftwareMetadata($version);

        $version = $version->refresh();

        VersionReview::create([
            'version_id' => $version->id,
            'user_id' => auth()->id(),
            'action' => ReviewAction::APPROVED,
        ]);

        $this->notificationService->notifyVersionApproved($version);

        return $version;
    }

    public function reject(Version $version, ?string $reason = null, RejectReason|string|null $rejectReason = null): Version
    {
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
