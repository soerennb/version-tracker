<?php

namespace App\Filament\Pages;

use App\Models\Software;
use App\Models\Version;
use App\Models\Vulnerability;
use BackedEnum;
use Filament\Pages\Page;

class AnalyticsDashboard extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.pages.analytics-dashboard';

    public static function getNavigationLabel(): string
    {
        return __('filament.navigation.analytics');
    }

    protected function getViewData(): array
    {
        $softwareCount = Software::count();

        $softwareFilters = Software::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $requestedSoftwareId = request()->integer('software');

        if ($softwareFilters->isEmpty()) {
            $activeSoftwareId = null;
        } elseif ($requestedSoftwareId && $softwareFilters->contains('id', $requestedSoftwareId)) {
            $activeSoftwareId = $requestedSoftwareId;
        } else {
            $activeSoftwareId = null;
        }

        $activeSoftware = $activeSoftwareId
            ? Software::query()->withCount('versions')->find($activeSoftwareId)
            : null;

        $complianceBreakdown = Software::query()
            ->selectRaw('compliance_status, count(*) as aggregate')
            ->groupBy('compliance_status')
            ->pluck('aggregate', 'compliance_status')
            ->toArray();

        return [
            'softwareCount' => $softwareCount,
            'publishedVersions' => Version::where('status', 'published')->count(),
            'pendingApprovals' => Version::where('approval_status', 'pending')->count(),
            'openVulnerabilities' => Vulnerability::count(),
            'complianceBreakdown' => $complianceBreakdown,
            'softwareFilters' => $softwareFilters,
            'activeSoftware' => $activeSoftware,
            'activeSoftwareId' => $activeSoftwareId,
            'timelineVersions' => Version::with('software')
                ->when($activeSoftwareId, fn ($query) => $query->where('software_id', $activeSoftwareId))
                ->latest('release_date')
                ->limit(8)
                ->get(),
        ];
    }
}
