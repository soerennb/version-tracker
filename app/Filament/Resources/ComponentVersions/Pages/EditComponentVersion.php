<?php

namespace App\Filament\Resources\ComponentVersions\Pages;

use App\Filament\Resources\ComponentVersions\ComponentVersionResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditComponentVersion extends EditRecord
{
    protected static string $resource = ComponentVersionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
