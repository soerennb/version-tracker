<?php

namespace App\Filament\Resources\Software\Pages;

use App\Filament\Resources\Software\SoftwareResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSoftware extends ListRecords
{
    protected static string $resource = SoftwareResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportCsv')
                ->label(__('filament.actions.export_csv'))
                ->icon('heroicon-o-arrow-down-tray')
                ->url(route('admin.exports.software.csv'))
                ->visible(fn (): bool => auth()->user() instanceof User && auth()->user()->can('export_data')),
            CreateAction::make(),
        ];
    }
}
