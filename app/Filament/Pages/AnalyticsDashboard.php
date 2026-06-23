<?php

namespace App\Filament\Pages;

use App\Services\AdminWorkQueueService;
use BackedEnum;
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
