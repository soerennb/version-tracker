<?php

namespace App\Filament\Widgets;

use App\Enums\SoftwareStatus;
use App\Enums\SourceSyncStatus;
use App\Models\Software;
use App\Models\SourceSyncRun;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class GithubSyncStatus extends StatsOverviewWidget
{
    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        return auth()->user()?->can('view_software') ?? false;
    }

    protected function getHeading(): ?string
    {
        return __('filament.widgets.github_sync.heading');
    }

    protected function getDescription(): ?string
    {
        return __('filament.widgets.github_sync.description');
    }

    protected function getStats(): array
    {
        $activeRepositories = Software::query()
            ->where('status', SoftwareStatus::ACTIVE->value)
            ->whereNotNull('github_repo_url')
            ->count();
        $activeRuns = SourceSyncRun::query()
            ->where('provider', 'github')
            ->whereIn('status', [SourceSyncStatus::QUEUED->value, SourceSyncStatus::RUNNING->value])
            ->count();
        $failedRuns = SourceSyncRun::query()
            ->where('provider', 'github')
            ->where('status', SourceSyncStatus::FAILED->value)
            ->where('created_at', '>=', now()->subDay())
            ->count();
        $lastSuccessfulRun = SourceSyncRun::query()
            ->where('provider', 'github')
            ->where('status', SourceSyncStatus::SUCCEEDED->value)
            ->whereNotNull('finished_at')
            ->latest('finished_at')
            ->first();

        return [
            Stat::make(__('filament.widgets.github_sync.active_repositories'), $activeRepositories)
                ->description(__('filament.widgets.github_sync.active_repositories_description'))
                ->descriptionIcon('heroicon-m-server-stack')
                ->color('info'),
            Stat::make(__('filament.widgets.github_sync.active_runs'), $activeRuns)
                ->description(__('filament.widgets.github_sync.active_runs_description'))
                ->descriptionIcon('heroicon-m-arrow-path')
                ->color($activeRuns > 0 ? 'warning' : 'gray'),
            Stat::make(__('filament.widgets.github_sync.failed_runs'), $failedRuns)
                ->description(__('filament.widgets.github_sync.failed_runs_description'))
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color($failedRuns > 0 ? 'danger' : 'success'),
            Stat::make(
                __('filament.widgets.github_sync.last_success'),
                $lastSuccessfulRun?->finished_at?->diffForHumans() ?? __('filament.widgets.github_sync.never'),
            )
                ->description(__('filament.widgets.github_sync.last_success_description'))
                ->descriptionIcon('heroicon-m-check-circle')
                ->color($lastSuccessfulRun ? 'success' : 'gray'),
        ];
    }
}
