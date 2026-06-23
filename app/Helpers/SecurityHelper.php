<?php

namespace App\Helpers;

use App\Enums\VulnerabilitySeverity;
use App\Models\Software;
use App\Models\Vulnerability;
use Illuminate\Support\Facades\Cache;

class SecurityHelper
{
    public static function calculateVulnerabilityScore(Vulnerability $vulnerability): float
    {
        $baseScore = match ($vulnerability->severity) {
            VulnerabilitySeverity::CRITICAL => 9.0,
            VulnerabilitySeverity::HIGH => 7.0,
            VulnerabilitySeverity::MEDIUM => 5.0,
            VulnerabilitySeverity::LOW => 2.0,
            default => 0.0,
        };

        $daysSincePublished = now()->diffInDays($vulnerability->published_date);
        $timeFactor = max(0.5, 1.0 - ($daysSincePublished / 365));

        return round($baseScore * $timeFactor, 2);
    }

    public static function getSoftwareSecurityStatus(Software $software): string
    {
        $cacheKey = "software:{$software->id}:security";

        return Cache::remember($cacheKey, 3600, function () use ($software): string {
            $vulnerabilities = $software->versions()
                ->with('vulnerabilities')
                ->get()
                ->pluck('vulnerabilities')
                ->flatten();

            $critical = $vulnerabilities->where('severity', VulnerabilitySeverity::CRITICAL)->count();
            $high = $vulnerabilities->where('severity', VulnerabilitySeverity::HIGH)->count();

            return match (true) {
                $critical > 0 => 'critical',
                $high > 0 => 'high',
                default => 'secure',
            };
        });
    }

    public static function generateCveId(): string
    {
        $year = now()->year;
        $randomId = strtoupper(substr(sha1(uniqid((string) $year, true)), 0, 8));

        return "CVE-{$year}-{$randomId}";
    }
}
