<?php

namespace App\Filament\Resources\TrackedComponents\Pages;

use App\Filament\Resources\TrackedComponents\TrackedComponentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTrackedComponent extends EditRecord
{
    protected static string $resource = TrackedComponentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
