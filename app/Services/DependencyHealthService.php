<?php

namespace App\Services;

use App\Enums\VersionStatus;
use App\Enums\VulnerabilitySeverity;
use App\Enums\VulnerabilityStatus;
use App\Helpers\DependencyHelper;
use App\Helpers\VersionHelper;
use App\Models\SoftwareDependency;
use App\Models\Version;

class DependencyHealthService
{
    /**
     * @return array{status:string,label:string,color:string,detail:string}
     */
    public function evaluate(SoftwareDependency $dependency): array
    {
        $dependency->loadMissing([
            'dependsOnSoftware.versions.vulnerabilities',
            'maxVersion',
            'minVersion',
        ]);

        if (! DependencyHelper::validateDependency($dependency)) {
            return $this->result('broken', 'danger', __('dependencies.health.broken'), __('dependencies.health.broken_detail'));
        }

        if ($this->hasBlockingVulnerability($dependency)) {
            return $this->result('unsafe', 'danger', __('dependencies.health.unsafe'), __('dependencies.health.unsafe_detail'));
        }

        if ($this->hasEolRisk($dependency)) {
            return $this->result('eol_risk', 'warning', __('dependencies.health.eol_risk'), __('dependencies.health.eol_risk_detail'));
        }

        if ($this->isOutdated($dependency)) {
            return $this->result('outdated', 'warning', __('dependencies.health.outdated'), __('dependencies.health.outdated_detail'));
        }

        return $this->result('healthy', 'success', __('dependencies.health.healthy'), __('dependencies.health.healthy_detail'));
    }

    /**
     * @return array{status:string,label:string,color:string,detail:string}
     */
    protected function result(string $status, string $color, string $label, string $detail): array
    {
        return [
            'status' => $status,
            'label' => $label,
            'color' => $color,
            'detail' => $detail,
        ];
    }

    protected function hasBlockingVulnerability(SoftwareDependency $dependency): bool
    {
        return $dependency->dependsOnSoftware?->versions
            ->flatMap(fn (Version $version) => $version->vulnerabilities)
            ->contains(fn ($vulnerability): bool => $vulnerability->status === VulnerabilityStatus::OPEN
                && in_array($vulnerability->severity, [
                    VulnerabilitySeverity::CRITICAL,
                    VulnerabilitySeverity::HIGH,
                ], true)) ?? false;
    }

    protected function hasEolRisk(SoftwareDependency $dependency): bool
    {
        return $dependency->dependsOnSoftware?->versions
            ->contains(fn (Version $version): bool => $version->status === VersionStatus::PUBLISHED
                && $version->eol_date !== null
                && $version->eol_date->lte(now()->addDays(90))) ?? false;
    }

    protected function isOutdated(SoftwareDependency $dependency): bool
    {
        if (! $dependency->maxVersion) {
            return false;
        }

        $latestPublished = $dependency->dependsOnSoftware?->versions
            ->filter(fn (Version $version): bool => $version->status === VersionStatus::PUBLISHED && VersionHelper::isValidSemver($version->version_number))
            ->sort(fn (Version $left, Version $right): int => VersionHelper::compareVersions($right->version_number, $left->version_number))
            ->first();

        if (! $latestPublished || ! VersionHelper::isValidSemver($dependency->maxVersion->version_number)) {
            return false;
        }

        return VersionHelper::compareVersions($latestPublished->version_number, $dependency->maxVersion->version_number) > 0;
    }
}
