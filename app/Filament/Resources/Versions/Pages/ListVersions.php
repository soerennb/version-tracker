<?php

namespace App\Filament\Resources\Versions\Pages;

use App\Filament\Resources\Versions\VersionResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListVersions extends ListRecords
{
    protected static string $resource = VersionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('exportCsv')
                ->label(__('filament.actions.export_csv'))
                ->icon('heroicon-o-arrow-down-tray')
                ->url(route('admin.exports.versions.csv'))
                ->visible(fn (): bool => auth()->user() instanceof User && auth()->user()->can('export_data')),
            Action::make('exportPdf')
                ->label(__('filament.actions.export_pdf'))
                ->icon('heroicon-o-document-arrow-down')
                ->url(route('admin.exports.versions.pdf'))
                ->visible(fn (): bool => auth()->user() instanceof User && auth()->user()->can('export_data')),
            CreateAction::make(),
        ];
    }
}
