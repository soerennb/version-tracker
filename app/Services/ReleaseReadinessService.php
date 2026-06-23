<?php

namespace App\Services;

use App\Enums\Language;
use App\Enums\VulnerabilitySeverity;
use App\Enums\VulnerabilityStatus;
use App\Helpers\DependencyHelper;
use App\Models\SoftwareDependency;
use App\Models\Version;

class ReleaseReadinessService
{
    /**
     * @return array{score:int, passed:int, total:int, is_ready:bool, blockers:array<int, array{code:string,label:string}>}
     */
    public function evaluate(Version $version): array
    {
        $version->loadMissing([
            'fileAttachments',
            'software.dependenciesOutgoing.dependsOnSoftware',
            'software.dependenciesOutgoing.minVersion',
            'software.dependenciesOutgoing.maxVersion',
            'textContents',
            'vulnerabilities',
        ]);

        $checks = [
            $this->contentCheck($version),
            $this->securityCheck($version),
            $this->dependencyCheck($version),
            $this->attachmentsCheck($version),
            $this->lifecycleCheck($version),
        ];

        $blockers = array_values(array_filter($checks, fn (array $check): bool => ! $check['passed']));
        $passed = count($checks) - count($blockers);
        $total = count($checks);

        return [
            'score' => (int) round(($passed / max($total, 1)) * 100),
            'passed' => $passed,
            'total' => $total,
            'is_ready' => $passed === $total,
            'blockers' => array_map(fn (array $check): array => [
                'code' => $check['code'],
                'label' => $check['label'],
            ], $blockers),
        ];
    }

    /**
     * @return array{passed:bool, code:string, label:string}
     */
    protected function contentCheck(Version $version): array
    {
        $languages = $version->textContents
            ->pluck('language')
            ->map(fn (Language|string $language): string => $language instanceof Language ? $language->value : $language)
            ->all();

        return [
            'passed' => empty(array_diff(Language::values(), $languages)),
            'code' => 'missing_required_content',
            'label' => __('versions.readiness.missing_required_content'),
        ];
    }

    /**
     * @return array{passed:bool, code:string, label:string}
     */
    protected function securityCheck(Version $version): array
    {
        $hasBlockingVulnerabilities = $version->vulnerabilities
            ->contains(fn ($vulnerability): bool => $vulnerability->status === VulnerabilityStatus::OPEN
                && in_array($vulnerability->severity, [
                    VulnerabilitySeverity::CRITICAL,
                    VulnerabilitySeverity::HIGH,
                ], true));

        return [
            'passed' => ! $hasBlockingVulnerabilities,
            'code' => 'blocking_vulnerabilities',
            'label' => __('versions.readiness.blocking_vulnerabilities'),
        ];
    }

    /**
     * @return array{passed:bool, code:string, label:string}
     */
    protected function dependencyCheck(Version $version): array
    {
        $dependencies = $version->software?->dependenciesOutgoing ?? collect();

        $hasInvalidDependency = $dependencies
            ->contains(fn (SoftwareDependency $dependency): bool => ! DependencyHelper::validateDependency($dependency));

        return [
            'passed' => ! $hasInvalidDependency,
            'code' => 'invalid_dependencies',
            'label' => __('versions.readiness.invalid_dependencies'),
        ];
    }

    /**
     * @return array{passed:bool, code:string, label:string}
     */
    protected function attachmentsCheck(Version $version): array
    {
        return [
            'passed' => $version->fileAttachments->isNotEmpty(),
            'code' => 'missing_attachments',
            'label' => __('versions.readiness.missing_attachments'),
        ];
    }

    /**
     * @return array{passed:bool, code:string, label:string}
     */
    protected function lifecycleCheck(Version $version): array
    {
        return [
            'passed' => filled($version->support_status) || filled($version->eol_date) || filled($version->lts_date),
            'code' => 'missing_lifecycle',
            'label' => __('versions.readiness.missing_lifecycle'),
        ];
    }
}
