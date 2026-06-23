<?php

namespace App\Filament\Pages;

use App\Models\Software;
use App\Models\User;
use App\Services\DependencyMapService;
use BackedEnum;
use Filament\Pages\Page;

class DependencyMap extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-share';

    protected static ?int $navigationSort = 52;

    protected string $view = 'filament.pages.dependency-map';

    public ?int $selectedSoftwareId = null;

    public static function getNavigationGroup(): ?string
    {
        return __('filament.navigation.dependencies');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.navigation.dependency_map');
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->can('view_software');
    }

    public function mount(): void
    {
        $this->selectedSoftwareId = Software::query()->orderBy('name')->value('id');
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'softwareOptions' => Software::query()
                ->orderBy('name')
                ->pluck('name', 'id'),
            'map' => app(DependencyMapService::class)->build($this->selectedSoftwareId),
        ];
    }
}
