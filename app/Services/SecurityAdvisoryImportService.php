<?php

namespace App\Services;

use App\Enums\ExploitabilityStatus;
use App\Enums\VulnerabilitySeverity;
use App\Enums\VulnerabilityStatus;
use App\Models\Software;
use App\Models\Vulnerability;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class SecurityAdvisoryImportService
{
    /**
     * @return array{imported:int,updated:int,skipped:int,errors:array<int, string>}
     */
    public function importFromProvider(SecurityAdvisoryProvider $provider, bool $dryRun = false): array
    {
        return $this->import($provider->advisories(), $dryRun);
    }

    /**
     * @param  iterable<int, array<string, mixed>>  $advisories
     * @return array{imported:int,updated:int,skipped:int,errors:array<int, string>}
     */
    public function import(iterable $advisories, bool $dryRun = false): array
    {
        $result = [
            'imported' => 0,
            'updated' => 0,
            'skipped' => 0,
            'errors' => [],
        ];

        foreach ($advisories as $index => $advisory) {
            $normalized = $this->normalize($advisory);

            if ($normalized['error'] !== null) {
                $result['skipped']++;
                $result['errors'][] = 'Advisory '.$index.': '.$normalized['error'];

                continue;
            }

            if ($dryRun) {
                $result['imported']++;

                continue;
            }

            $vulnerability = Vulnerability::query()
                ->where('cve_id', $normalized['cve_id'])
                ->where('affected_version_id', $normalized['affected_version_id'])
                ->first();

            if ($vulnerability) {
                $vulnerability->fill($normalized['attributes']);
                $vulnerability->save();
                $result['updated']++;

                continue;
            }

            Vulnerability::create([
                'cve_id' => $normalized['cve_id'],
                'affected_version_id' => $normalized['affected_version_id'],
                ...$normalized['attributes'],
            ]);
            $result['imported']++;
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $advisory
     * @return array{error:?string,cve_id:?string,affected_version_id:?int,attributes:array<string, mixed>}
     */
    protected function normalize(array $advisory): array
    {
        $cveId = $this->stringValue($advisory, ['cve_id', 'id', 'aliases.0']);
        $softwareName = $this->stringValue($advisory, ['software_name', 'package.name', 'affected.package.name']);
        $versionNumber = $this->stringValue($advisory, ['version_number', 'affected_version', 'affected.version']);

        if (! $cveId || ! $softwareName || ! $versionNumber) {
            return $this->normalizationError('Missing cve_id, software_name, or version_number.');
        }

        $software = Software::query()
            ->where('name', $softwareName)
            ->first();

        if (! $software) {
            return $this->normalizationError('Software not found: '.$softwareName.'.');
        }

        $affectedVersion = $software->versions()
            ->where('version_number', $versionNumber)
            ->first();

        if (! $affectedVersion) {
            return $this->normalizationError('Version not found: '.$softwareName.' '.$versionNumber.'.');
        }

        $fixedVersion = null;
        $fixedVersionNumber = $this->stringValue($advisory, ['fixed_version', 'fixed_version_number']);

        if ($fixedVersionNumber) {
            $fixedVersion = $software->versions()
                ->where('version_number', $fixedVersionNumber)
                ->first();
        }

        return [
            'error' => null,
            'cve_id' => Str::upper($cveId),
            'affected_version_id' => $affectedVersion->id,
            'attributes' => [
                'severity' => $this->severity($this->stringValue($advisory, ['severity', 'database_specific.severity'])),
                'description' => $this->stringValue($advisory, ['description', 'summary']) ?? '',
                'published_date' => $this->stringValue($advisory, ['published_date', 'published']) ?? now()->toDateString(),
                'cvss_score' => $this->floatValue($advisory, ['cvss_score', 'cvss.score', 'severity_score']),
                'source' => $this->stringValue($advisory, ['source', 'provider']) ?? 'manual',
                'source_url' => $this->stringValue($advisory, ['source_url', 'references.0.url']),
                'affected_range' => $this->stringValue($advisory, ['affected_range', 'ranges.0.events.0.introduced']),
                'fixed_version_id' => $fixedVersion?->id,
                'status' => $this->status($this->stringValue($advisory, ['status'])),
                'exploitability' => $this->exploitability($this->stringValue($advisory, ['exploitability'])),
            ],
        ];
    }

    /**
     * @return array{error:string,cve_id:null,affected_version_id:null,attributes:array<string, mixed>}
     */
    protected function normalizationError(string $message): array
    {
        return [
            'error' => $message,
            'cve_id' => null,
            'affected_version_id' => null,
            'attributes' => [],
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $paths
     */
    protected function stringValue(array $payload, array $paths): ?string
    {
        foreach ($paths as $path) {
            $value = Arr::get($payload, $path);

            if (is_scalar($value) && filled((string) $value)) {
                return (string) $value;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  array<int, string>  $paths
     */
    protected function floatValue(array $payload, array $paths): ?float
    {
        foreach ($paths as $path) {
            $value = Arr::get($payload, $path);

            if (is_numeric($value)) {
                return (float) $value;
            }
        }

        return null;
    }

    protected function severity(?string $severity): VulnerabilitySeverity
    {
        return VulnerabilitySeverity::tryFrom(Str::lower((string) $severity)) ?? VulnerabilitySeverity::MEDIUM;
    }

    protected function status(?string $status): VulnerabilityStatus
    {
        return VulnerabilityStatus::tryFrom(Str::lower((string) $status)) ?? VulnerabilityStatus::OPEN;
    }

    protected function exploitability(?string $exploitability): ExploitabilityStatus
    {
        return ExploitabilityStatus::tryFrom(Str::lower((string) $exploitability)) ?? ExploitabilityStatus::UNKNOWN;
    }
}
