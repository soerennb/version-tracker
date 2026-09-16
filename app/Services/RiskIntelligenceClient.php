<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class RiskIntelligenceClient
{
    public function enabled(): bool
    {
        return (bool) config('services.risk_intelligence.enabled', false)
            || app(RuntimeSettings::class)->security()->risk_intelligence_enabled;
    }

    /**
     * @param  array<int, string>  $identifiers
     * @return array<string, array{epss_score:?float,is_kev:bool}>
     */
    public function lookup(array $identifiers): array
    {
        $cves = collect($identifiers)
            ->map(fn (string $identifier): string => Str::upper(trim($identifier)))
            ->filter(fn (string $identifier): bool => preg_match('/^CVE-\d{4}-\d{4,}$/', $identifier) === 1)
            ->unique()
            ->values()
            ->all();

        if ($cves === []) {
            return [];
        }

        $epss = $this->epss($cves);
        $kev = $this->kev();
        $result = [];

        foreach ($cves as $cve) {
            $result[$cve] = [
                'epss_score' => $epss[$cve] ?? null,
                'is_kev' => in_array($cve, $kev, true),
            ];
        }

        return $result;
    }

    /**
     * @param  array<int, string>  $cves
     * @return array<string, float>
     */
    private function epss(array $cves): array
    {
        $rows = $this->request()
            ->get((string) config('services.risk_intelligence.epss_url'), [
                'cve' => implode(',', $cves),
            ])
            ->throw()
            ->json('data', []);

        $scores = [];
        foreach ((array) $rows as $row) {
            if (! is_array($row) || ! filled($row['cve'] ?? null) || ! is_numeric($row['epss'] ?? null)) {
                continue;
            }

            $scores[Str::upper((string) $row['cve'])] = max(0.0, min(1.0, (float) $row['epss']));
        }

        return $scores;
    }

    /**
     * @return array<int, string>
     */
    private function kev(): array
    {
        $rows = $this->request()
            ->get((string) config('services.risk_intelligence.kev_url'))
            ->throw()
            ->json('vulnerabilities', []);

        return collect((array) $rows)
            ->filter(static fn (mixed $row): bool => is_array($row) && filled($row['cveID'] ?? null))
            ->map(static fn (array $row): string => Str::upper((string) $row['cveID']))
            ->unique()
            ->values()
            ->all();
    }

    private function request(): PendingRequest
    {
        return Http::acceptJson()
            ->timeout(max((int) config('services.risk_intelligence.timeout', 15), 1))
            ->retry(max((int) config('services.risk_intelligence.retry_times', 2), 0), 250, throw: false);
    }
}
