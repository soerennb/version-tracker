<?php

namespace App\Filament\Resources\Deployments\Pages;

use App\Filament\Resources\Deployments\DeploymentResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListDeployments extends ListRecords
{
    protected static string $resource = DeploymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportCsv')
                ->label(__('filament.actions.export_deployments_csv'))
                ->icon('heroicon-o-arrow-down-tray')
                ->url(route('admin.exports.deployments.csv'))
                ->visible(fn (): bool => auth()->user() instanceof User && auth()->user()->can('export_deployments')),
            CreateAction::make(),
        ];
    }
}
