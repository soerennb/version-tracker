<?php

namespace App\Filament\Pages;

use App\Enums\VersionStatus;
use App\Enums\VulnerabilitySeverity;
use App\Enums\VulnerabilityStatus;
use App\Models\User;
use App\Models\Version;
use App\Models\Vulnerability;
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
            ->whereIn('vulnerabilities.severity', [VulnerabilitySeverity::CRITICAL->value, VulnerabilitySeverity::HIGH->value]);

        $priorityFindings = (clone $openCriticalOrHigh)
            ->with(['affectedVersion.software', 'fixedVersion'])
            ->orderByDesc('cvss_score')
            ->latest('published_date')
            ->limit(10)
            ->get();

        return [
            'openCriticalOrHigh' => (clone $openCriticalOrHigh)->count(),
            'fixAvailable' => (clone $openCriticalOrHigh)->whereNotNull('fixed_version_id')->count(),
            'eolRiskCount' => Version::query()
                ->where('status', VersionStatus::PUBLISHED->value)
                ->whereNotNull('eol_date')
                ->whereDate('eol_date', '<=', now()->addDays(90))
                ->count(),
            'affectedSoftwareCount' => (clone $openCriticalOrHigh)
                ->whereHas('affectedVersion.software')
                ->join('versions', 'vulnerabilities.affected_version_id', '=', 'versions.id')
                ->count(DB::raw('distinct versions.software_id')),
            'priorityFindings' => $priorityFindings,
            'severityBreakdown' => $this->severityBreakdown(),
        ];
    }

    /**
     * @return Collection<string, int>
     */
    protected function severityBreakdown(): Collection
    {
        return Vulnerability::query()
            ->where('vulnerabilities.status', VulnerabilityStatus::OPEN->value)
            ->selectRaw('severity, count(*) as aggregate')
            ->groupBy('severity')
            ->pluck('aggregate', 'severity');
    }
}
