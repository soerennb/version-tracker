<?php

namespace App\Services;

use App\Enums\ExploitabilityStatus;
use App\Enums\VulnerabilitySeverity;
use App\Enums\VulnerabilityStatus;
use App\Models\ComponentFinding;
use App\Models\SbomDocument;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Throwable;

class SbomEnrichmentService
{
    public function __construct(
        protected OsvClient $client,
        protected RiskIntelligenceClient $riskIntelligence,
    ) {}

    /**
     * Enrich a parsed SBOM from OSV using package URLs, preserving source evidence.
     *
     * @return array{created:int,updated:int,skipped:int,risk_updated:int,error:?string}
     */
    public function enrich(SbomDocument $document): array
    {
        $document->loadMissing('components');
        $queries = [];
        $components = [];
        $skipped = 0;

        foreach ($document->components as $component) {
            if (! filled($component->purl)) {
                $skipped++;

                continue;
            }

            $query = ['package' => ['purl' => $component->purl]];
            if (filled($component->version)) {
                $query['version'] = $component->version;
            }
            $queries[] = $query;
            $components[] = $component;
        }

        if ($queries === []) {
            $document->forceFill([
                'enrichment_status' => 'skipped',
                'last_enriched_at' => now(),
                'enrichment_error' => null,
            ])->save();

            return ['created' => 0, 'updated' => 0, 'skipped' => $skipped, 'risk_updated' => 0, 'error' => null];
        }

        try {
            $results = $this->client->queryBatch($queries);
        } catch (Throwable $exception) {
            $document->forceFill([
                'enrichment_status' => 'failed',
                'enrichment_error' => Str::limit($exception->getMessage(), 1000),
            ])->save();

            return ['created' => 0, 'updated' => 0, 'skipped' => $skipped, 'risk_updated' => 0, 'error' => $exception->getMessage()];
        }

        $created = 0;
        $updated = 0;
        foreach ($components as $index => $component) {
            $result = is_array($results[$index] ?? null) ? $results[$index] : [];
            foreach ((array) ($result['vulns'] ?? []) as $advisory) {
                if (! is_array($advisory) || ! filled($advisory['id'] ?? null)) {
                    continue;
                }

                $attributes = $this->normalizeAdvisory($advisory);
                $finding = ComponentFinding::query()->firstOrNew([
                    'sbom_document_id' => $document->id,
                    'sbom_component_id' => $component->id,
                    'external_id' => $attributes['external_id'],
                ]);
                $wasExisting = $finding->exists;
                $finding->fill($attributes);
                $finding->forceFill([
                    'last_seen_at' => now(),
                    'first_seen_at' => $finding->first_seen_at ?? now(),
                ])->save();

                $wasExisting ? $updated++ : $created++;
            }
        }

        $riskUpdated = $this->enrichRisk($document);

        $document->forceFill([
            'enrichment_status' => 'succeeded',
            'last_enriched_at' => now(),
            'enrichment_error' => null,
            'finding_count' => $document->findings()->count(),
        ])->save();

        return ['created' => $created, 'updated' => $updated, 'skipped' => $skipped, 'risk_updated' => $riskUpdated, 'error' => null];
    }

    private function enrichRisk(SbomDocument $document): int
    {
        if (! $this->riskIntelligence->enabled()) {
            return 0;
        }

        $findings = ComponentFinding::query()
            ->where('sbom_document_id', $document->id)
            ->get();
        if ($findings->isEmpty()) {
            return 0;
        }

        $identifiers = $findings
            ->flatMap(fn (ComponentFinding $finding): array => [
                $finding->external_id,
                ...array_values(array_filter((array) ($finding->details['aliases'] ?? []), 'is_scalar')),
            ])
            ->filter(fn (mixed $identifier): bool => is_string($identifier) && filled($identifier))
            ->unique()
            ->values()
            ->all();

        try {
            $intelligence = $this->riskIntelligence->lookup($identifiers);
        } catch (Throwable) {
            return 0;
        }

        $updated = 0;
        foreach ($findings as $finding) {
            $match = $this->findRiskMatch($finding, $intelligence);
            if ($match === null) {
                continue;
            }

            $factors = [];
            if ($match['is_kev']) {
                $factors[] = 'kev';
            }
            if ($match['epss_score'] !== null && $match['epss_score'] >= 0.1) {
                $factors[] = 'epss_elevated';
            }
            if ($finding->exploitability === ExploitabilityStatus::ACTIVE) {
                $factors[] = 'active_exploitation';
            }

            $finding->forceFill([
                'epss_score' => $match['epss_score'],
                'is_kev' => $match['is_kev'],
                'risk_score' => $this->riskScore($finding, $match['epss_score'], $match['is_kev']),
                'risk_factors' => $factors,
            ])->save();
            $updated++;
        }

        return $updated;
    }

    /**
     * @param  array<string, array{epss_score:?float,is_kev:bool}>  $intelligence
     * @return array{epss_score:?float,is_kev:bool}|null
     */
    private function findRiskMatch(ComponentFinding $finding, array $intelligence): ?array
    {
        $identifiers = [
            $finding->external_id,
            ...array_values(array_filter((array) ($finding->details['aliases'] ?? []), 'is_scalar')),
        ];

        foreach ($identifiers as $identifier) {
            $key = Str::upper((string) $identifier);
            if (isset($intelligence[$key])) {
                return $intelligence[$key];
            }
        }

        return null;
    }

    private function riskScore(ComponentFinding $finding, ?float $epss, bool $isKev): float
    {
        $score = ((float) ($finding->cvss_score ?? 0)) * 6;
        $score += ($epss ?? 0) * 25;
        $score += $isKev ? 15 : 0;
        $score += $finding->exploitability === ExploitabilityStatus::ACTIVE ? 10 : 0;

        return round(min($score, 100), 2);
    }

    /**
     * @param  array<string, mixed>  $advisory
     * @return array<string, mixed>
     */
    private function normalizeAdvisory(array $advisory): array
    {
        $severity = Arr::get($advisory, 'database_specific.severity')
            ?? Arr::get($advisory, 'severity.0.type');
        $aliases = array_values(array_filter((array) ($advisory['aliases'] ?? []), 'is_scalar'));
        $sourceUrl = collect((array) ($advisory['references'] ?? []))
            ->filter(static fn (mixed $reference): bool => is_array($reference))
            ->map(static fn (array $reference): mixed => $reference['url'] ?? null)
            ->filter()
            ->first();

        return [
            'source' => 'OSV',
            'severity' => $this->severity($severity),
            'cvss_score' => null,
            'exploitability' => ExploitabilityStatus::UNKNOWN,
            'status' => VulnerabilityStatus::OPEN,
            'description' => $advisory['summary'] ?? $advisory['details'] ?? null,
            'source_url' => is_string($sourceUrl) ? $sourceUrl : null,
            'details' => ['aliases' => $aliases, 'osv_id' => (string) $advisory['id']],
            'external_id' => (string) $advisory['id'],
            'affected_range' => null,
            'fixed_version' => $this->fixedVersion($advisory),
        ];
    }

    private function severity(mixed $severity): ?VulnerabilitySeverity
    {
        return match (Str::lower(trim((string) $severity))) {
            'critical' => VulnerabilitySeverity::CRITICAL,
            'high', 'important' => VulnerabilitySeverity::HIGH,
            'medium', 'moderate' => VulnerabilitySeverity::MEDIUM,
            'low', 'negligible' => VulnerabilitySeverity::LOW,
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $advisory
     */
    private function fixedVersion(array $advisory): ?string
    {
        foreach ((array) ($advisory['affected'] ?? []) as $affected) {
            foreach ((array) ($affected['ranges'] ?? []) as $range) {
                foreach ((array) ($range['events'] ?? []) as $event) {
                    if (is_array($event) && filled($event['fixed'] ?? null)) {
                        return (string) $event['fixed'];
                    }
                }
            }
        }

        return null;
    }
}
