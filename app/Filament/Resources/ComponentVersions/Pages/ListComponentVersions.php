<?php

namespace App\Filament\Resources\ComponentVersions\Pages;

use App\Filament\Resources\ComponentVersions\ComponentVersionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListComponentVersions extends ListRecords
{
    protected static string $resource = ComponentVersionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
