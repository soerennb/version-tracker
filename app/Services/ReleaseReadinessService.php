<?php

namespace App\Services;

use App\Enums\ExploitabilityStatus;
use App\Enums\Language;
use App\Enums\VulnerabilitySeverity;
use App\Enums\VulnerabilityStatus;
use App\Helpers\DependencyHelper;
use App\Models\ComponentFinding;
use App\Models\ReleaseException;
use App\Models\SbomDocument;
use App\Models\SoftwareDependency;
use App\Models\Version;
use Illuminate\Support\Collection;

class ReleaseReadinessService
{
    /**
     * @return array{score:int,passed:int,total:int,is_ready:bool,blockers:array<int,array{code:string,label:string}>,risks:array<string,mixed>,exceptions:array<int,array<string,mixed>>,sbom:array<string,mixed>}
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
            'sbomDocuments.findings',
            'releaseExceptions.owner',
            'composition.supportedInterfaces',
            'composition.baselineVersion.component',
            'composition.eformsComponentVersion.component',
            'composition.activeEformsSdkVersion.component',
        ]);

        $governance = app(RuntimeSettings::class)->governance();
        $security = $this->securityCheck($version);
        $sbom = $this->sbomCheck($version);
        $checks = [
            $this->contentCheck($version),
            ...($version->software?->tracks_release_composition ? [$this->compositionCheck($version)] : []),
            ...($governance->require_security_clearance ? [$security] : []),
            ...($governance->require_dependency_validation ? [$this->dependencyCheck($version)] : []),
            ...($governance->require_attachments ? [$this->attachmentsCheck($version)] : []),
            ...($governance->require_lifecycle ? [$this->lifecycleCheck($version)] : []),
            ...($governance->require_sbom ? [$sbom] : []),
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
            'risks' => $security['risks'] ?? [],
            'exceptions' => $this->activeExceptions($version)
                ->map(fn (ReleaseException $exception): array => [
                    'id' => $exception->id,
                    'check_code' => $exception->check_code,
                    'reason' => $exception->reason,
                    'expires_at' => $exception->expires_at?->toISOString(),
                    'owner_id' => $exception->owner_id,
                    'owner_name' => $exception->owner?->name,
                ])
                ->values()
                ->all(),
            'sbom' => $sbom['details'] ?? [],
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

        $requiredLanguages = app(RuntimeSettings::class)->governance()->required_content_languages;

        return [
            'passed' => empty(array_diff($requiredLanguages, $languages)),
            'code' => 'missing_required_content',
            'label' => __('versions.readiness.missing_required_content'),
        ];
    }

    /**
     * @return array{passed:bool, code:string, label:string}
     */
    protected function securityCheck(Version $version): array
    {
        $settings = app(RuntimeSettings::class);
        $blockingVulnerabilities = $version->vulnerabilities
            ->filter(fn ($vulnerability): bool => $vulnerability->status === VulnerabilityStatus::OPEN
                && ($settings->isBlockingSeverity($vulnerability->severity)
                    || ($settings->governance()->block_active_exploits
                        && $vulnerability->exploitability === ExploitabilityStatus::ACTIVE)))
            ->map(fn ($vulnerability): array => [
                'type' => 'vulnerability',
                'id' => $vulnerability->id,
                'external_id' => $vulnerability->cve_id ?: $vulnerability->external_id,
                'severity' => $vulnerability->severity?->value,
                'exploitability' => $vulnerability->exploitability?->value,
            ])
            ->values()
            ->all();
        $latestSbom = $this->latestSbomDocument($version);
        $blockingFindings = ($latestSbom?->findings ?? collect())
            ->filter(fn (ComponentFinding $finding): bool => $finding->status === VulnerabilityStatus::OPEN
                && ($finding->is_kev
                    || $settings->isBlockingSeverity($finding->severity)
                    || ($settings->governance()->block_active_exploits
                        && $finding->exploitability === ExploitabilityStatus::ACTIVE)))
            ->map(fn (ComponentFinding $finding): array => [
                'type' => 'component_finding',
                'id' => $finding->id,
                'external_id' => $finding->external_id,
                'severity' => $finding->severity?->value,
                'exploitability' => $finding->exploitability?->value,
                'component_id' => $finding->sbom_component_id,
            ])
            ->values()
            ->all();
        $risks = collect($blockingVulnerabilities)->merge($blockingFindings)->values();
        $hasException = $this->hasActiveException($version, 'blocking_vulnerabilities');

        return [
            'passed' => $risks->isEmpty() || $hasException,
            'code' => 'blocking_vulnerabilities',
            'label' => $blockingFindings !== []
                ? __('versions.readiness.blocking_component_findings')
                : __('versions.readiness.blocking_vulnerabilities'),
            'risks' => [
                'total' => $risks->count(),
                'critical' => $risks->where('severity', VulnerabilitySeverity::CRITICAL->value)->count(),
                'high' => $risks->where('severity', VulnerabilitySeverity::HIGH->value)->count(),
                'active' => $risks->where('exploitability', ExploitabilityStatus::ACTIVE->value)->count(),
                'items' => $risks->take(100)->all(),
                'exception_applied' => $hasException,
            ],
        ];
    }

    /**
     * @return array{passed:bool,code:string,label:string,details:array<string,mixed>}
     */
    protected function sbomCheck(Version $version): array
    {
        $latest = $this->latestSbomDocument($version);
        $maxAge = max((int) app(RuntimeSettings::class)->governance()->sbom_max_age_days, 1);
        $parsedAt = $latest?->parsed_at ?? $latest?->created_at;
        $ageDays = $parsedAt ? (int) $parsedAt->diffInDays(now()) : null;
        $fresh = $latest !== null && ($ageDays === null || $ageDays <= $maxAge);
        $exceptionCode = $latest === null ? 'missing_sbom' : 'stale_sbom';
        $hasException = $this->hasActiveException($version, $exceptionCode);

        return [
            'passed' => $fresh || $hasException,
            'code' => $exceptionCode,
            'label' => $latest === null
                ? __('versions.readiness.missing_sbom')
                : __('versions.readiness.stale_sbom'),
            'details' => [
                'document_id' => $latest?->id,
                'format' => $latest?->format,
                'parsed_at' => $parsedAt?->toISOString(),
                'age_days' => $ageDays,
                'max_age_days' => $maxAge,
                'component_count' => $latest?->component_count ?? 0,
                'finding_count' => $latest?->finding_count ?? 0,
                'exception_applied' => $hasException,
            ],
        ];
    }

    /**
     * @return Collection<int, ReleaseException>
     */
    protected function activeExceptions(Version $version): Collection
    {
        return $version->releaseExceptions
            ->filter(fn (ReleaseException $exception): bool => $exception->isActive())
            ->sortBy('expires_at')
            ->values();
    }

    protected function hasActiveException(Version $version, string $checkCode): bool
    {
        return $this->activeExceptions($version)->contains(fn (ReleaseException $exception): bool => $exception->check_code === $checkCode);
    }

    protected function latestSbomDocument(Version $version): ?SbomDocument
    {
        return $version->sbomDocuments
            ->where('status', 'processed')
            ->sortByDesc(function (SbomDocument $document): string {
                $timestamp = $document->parsed_at?->getTimestamp() ?? $document->created_at?->getTimestamp() ?? 0;

                return sprintf('%020d%020d', $timestamp, $document->id);
            })
            ->first();
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

    /** @return array{passed:bool,code:string,label:string} */
    protected function compositionCheck(Version $version): array
    {
        return [
            'passed' => app(ReleaseCompositionService::class)->isComplete($version),
            'code' => 'missing_release_composition',
            'label' => __('versions.readiness.missing_release_composition'),
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
