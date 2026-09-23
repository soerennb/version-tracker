<?php

namespace App\Filament\Pages;

use App\Enums\DeploymentStatus;
use App\Models\Deployment;
use App\Models\Environment;
use App\Models\User;
use BackedEnum;
use Filament\Pages\Page;
use Illuminate\Support\Collection;

class InstalledReleases extends Page
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-computer-desktop';

    protected static ?int $navigationSort = 22;

    protected string $view = 'filament.pages.installed-releases';

    public ?int $selectedEnvironmentId = null;

    public static function getNavigationGroup(): ?string
    {
        return __('filament.navigation.operations');
    }

    public static function getNavigationLabel(): string
    {
        return __('filament.composition.installed_releases');
    }

    public static function canAccess(): bool
    {
        return auth()->user() instanceof User && auth()->user()->can('view_deployments');
    }

    public function mount(): void
    {
        $this->selectedEnvironmentId = Environment::query()->orderBy('name')->value('id');
    }

    /** @return array<string, mixed> */
    protected function getViewData(): array
    {
        return [
            'environments' => Environment::query()->with('customer')->orderBy('name')->get(),
            'installations' => $this->installations(),
        ];
    }

    /** @return Collection<int, Deployment> */
    private function installations(): Collection
    {
        if ($this->selectedEnvironmentId === null) {
            return collect();
        }

        return Deployment::query()
            ->with(['software', 'version.composition.baselineVersion', 'version.composition.eformsComponentVersion', 'version.composition.activeEformsSdkVersion', 'version.composition.supportedInterfaces.componentVersion.component', 'customizationVersion'])
            ->where('environment_id', $this->selectedEnvironmentId)
            ->where('status', DeploymentStatus::SUCCEEDED->value)
            ->orderByDesc('completed_at')
            ->orderByDesc('id')
            ->get()
            ->unique('software_id')
            ->values();
    }
}
