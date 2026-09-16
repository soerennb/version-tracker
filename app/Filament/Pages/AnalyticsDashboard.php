<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\GithubSyncStatus;
use App\Filament\Widgets\LatestGithubMessages;
use App\Models\User;
use App\Services\AdminWorkQueueService;
use App\Services\GitHubReleaseSyncDispatcher;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class AnalyticsDashboard extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-queue-list';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.analytics-dashboard';

    public static function getNavigationLabel(): string
    {
        return __('filament.navigation.analytics');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncAllGithubRepositories')
                ->label(__('filament.actions.sync_github_repositories'))
                ->icon('heroicon-o-arrow-path')
                ->visible(fn (): bool => auth()->user()?->can('manage_advanced_settings') ?? false)
                ->requiresConfirmation()
                ->action(function (): void {
                    $actor = auth()->user();

                    abort_unless($actor instanceof User && $actor->can('manage_advanced_settings'), 403);

                    $result = app(GitHubReleaseSyncDispatcher::class)->dispatchActiveRepositories();

                    Notification::make()
                        ->title(__('filament.messages.github_sync_all_queued'))
                        ->body(__('filament.messages.github_sync_all_queued_description', $result))
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            GithubSyncStatus::class,
            LatestGithubMessages::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'queues' => app(AdminWorkQueueService::class)->queues(),
        ];
    }
}
