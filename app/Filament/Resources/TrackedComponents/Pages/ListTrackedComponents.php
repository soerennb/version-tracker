<?php

namespace App\Filament\Resources\TrackedComponents\Pages;

use App\Filament\Resources\TrackedComponents\TrackedComponentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTrackedComponents extends ListRecords
{
    protected static string $resource = TrackedComponentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
