<?php

namespace App\Filament\Pages;

use App\Enums\ExploitabilityStatus;
use App\Enums\VersionStatus;
use App\Enums\VulnerabilityStatus;
use App\Models\ComponentFinding;
use App\Models\User;
use App\Models\Version;
use App\Models\Vulnerability;
use App\Services\RuntimeSettings;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SecurityDashboard extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-exclamation';

    protected static ?int $navigationSort = 51;

    protected string $view = 'filament.pages.security-dashboard';

    public static function getNavigationGroup(): ?string
    {
        return __('filament.navigation.security');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.navigation.security');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->can('view_vulnerabilities');
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $openCriticalOrHigh = Vulnerability::query()
            ->where('vulnerabilities.status', VulnerabilityStatus::OPEN->value)
            ->whereIn('vulnerabilities.severity', app(RuntimeSettings::class)->governance()->blocking_vulnerability_severities);

        $priorityFindings = (clone $openCriticalOrHigh)
            ->with(['affectedVersion.software', 'fixedVersion'])
            ->orderByDesc('cvss_score')
            ->latest('published_date')
            ->limit(10)
            ->get();
        $componentBlockers = ComponentFinding::query()
            ->where('status', VulnerabilityStatus::OPEN->value)
            ->where(function ($query): void {
                $query->whereIn('severity', app(RuntimeSettings::class)->governance()->blocking_vulnerability_severities)
                    ->orWhere('is_kev', true)
                    ->orWhere(function ($query): void {
                        $query->where('exploitability', ExploitabilityStatus::ACTIVE->value)
                            ->when(
                                ! app(RuntimeSettings::class)->governance()->block_active_exploits,
                                fn ($query) => $query->whereRaw('1 = 0'),
                            );
                    });
            });
        $priorityComponentFindings = (clone $componentBlockers)
            ->with(['component', 'document.version.software'])
            ->orderByDesc('risk_score')
            ->orderByDesc('cvss_score')
            ->latest('last_seen_at')
            ->limit(10)
            ->get();

        return [
            'openCriticalOrHigh' => (clone $openCriticalOrHigh)->count(),
            'openComponentFindings' => (clone $componentBlockers)->count(),
            'kevCount' => (clone $componentBlockers)->where('is_kev', true)->count(),
            'fixAvailable' => (clone $openCriticalOrHigh)->whereNotNull('fixed_version_id')->count()
                + (clone $componentBlockers)->whereNotNull('fixed_version')->count(),
            'eolRiskCount' => Version::query()
                ->where('status', VersionStatus::PUBLISHED->value)
                ->whereNotNull('eol_date')
                ->whereDate('eol_date', '<=', now()->addDays(app(RuntimeSettings::class)->notifications()->eol_alert_horizon_days))
                ->count(),
            'affectedSoftwareCount' => (clone $openCriticalOrHigh)
                ->whereHas('affectedVersion.software')
                ->join('versions', 'vulnerabilities.affected_version_id', '=', 'versions.id')
                ->count(DB::raw('distinct versions.software_id')),
            'priorityFindings' => $priorityFindings,
            'priorityComponentFindings' => $priorityComponentFindings,
            'severityBreakdown' => $this->severityBreakdown(),
        ];
    }

    /**
     * @return Collection<string, int>
     */
    protected function severityBreakdown(): Collection
    {
        $legacy = Vulnerability::query()
            ->where('vulnerabilities.status', VulnerabilityStatus::OPEN->value)
            ->selectRaw('severity, count(*) as aggregate')
            ->groupBy('severity')
            ->pluck('aggregate', 'severity');

        $component = ComponentFinding::query()
            ->where('component_findings.status', VulnerabilityStatus::OPEN->value)
            ->selectRaw('severity, count(*) as aggregate')
            ->groupBy('severity')
            ->pluck('aggregate', 'severity');

        foreach ($component as $severity => $count) {
            $legacy[$severity] = (int) ($legacy[$severity] ?? 0) + (int) $count;
        }

        return $legacy;
    }
}
