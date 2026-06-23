<?php

namespace App\Services;

use App\Enums\VersionStatus;
use App\Enums\VulnerabilitySeverity;
use App\Enums\VulnerabilityStatus;
use App\Helpers\VersionHelper;
use App\Models\User;
use App\Models\Version;
use Illuminate\Auth\Access\AuthorizationException;

class VersionGovernanceService
{
    public function assertCanApprove(User $user, Version $version): void
    {
        if (! $this->requiresIndependentApproval($version)) {
            return;
        }

        if ($version->created_by !== null && $version->created_by === $user->id) {
            throw new AuthorizationException(__('versions.governance.four_eyes_required'));
        }
    }

    public function requiresIndependentApproval(Version $version): bool
    {
        return (bool) config('release_governance.require_four_eyes_for_critical_releases')
            && $this->isCriticalRelease($version);
    }

    public function isCriticalRelease(Version $version): bool
    {
        $version->loadMissing(['software.versions', 'vulnerabilities']);

        return $this->isMajorVersionIncrease($version)
            || $this->hasBlockingVulnerabilities($version);
    }

    protected function isMajorVersionIncrease(Version $version): bool
    {
        $currentVersion = $version->software?->versions
            ->filter(fn (Version $candidate): bool => $candidate->id !== $version->id && $candidate->status === VersionStatus::PUBLISHED)
            ->sortByDesc('release_date')
            ->first();

        if (! $currentVersion || ! VersionHelper::isValidSemver($currentVersion->version_number) || ! VersionHelper::isValidSemver($version->version_number)) {
            return false;
        }

        return (int) explode('.', $version->version_number)[0] > (int) explode('.', $currentVersion->version_number)[0];
    }

    protected function hasBlockingVulnerabilities(Version $version): bool
    {
        return $version->vulnerabilities
            ->contains(fn ($vulnerability): bool => $vulnerability->status === VulnerabilityStatus::OPEN
                && in_array($vulnerability->severity, [
                    VulnerabilitySeverity::CRITICAL,
                    VulnerabilitySeverity::HIGH,
                ], true));
    }
}
