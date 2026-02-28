<?php

namespace App\Services;

use App\Enums\ApprovalStatus;
use App\Enums\VersionStatus;
use App\Models\Version;

class VersionService
{
    public function __construct(
        protected NotificationService $notificationService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Version
    {
        unset($data['status'], $data['approval_status']);

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
        $version->approval_status = ApprovalStatus::APPROVED;
        $version->status = VersionStatus::PUBLISHED;
        $version->save();

        $this->syncSoftwareMetadata($version);

        $version = $version->refresh();

        $this->notificationService->notifyVersionApproved($version);

        return $version;
    }

    public function reject(Version $version, ?string $reason = null): Version
    {
        $version->approval_status = ApprovalStatus::REJECTED;
        $version->status = VersionStatus::DRAFT;
        $version->support_status = $reason;
        $version->save();

        return $version->refresh();
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
