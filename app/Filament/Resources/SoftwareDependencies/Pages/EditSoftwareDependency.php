<?php

namespace App\Filament\Resources\SoftwareDependencies\Pages;

use App\Filament\Resources\SoftwareDependencies\SoftwareDependencyResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSoftwareDependency extends EditRecord
{
    protected static string $resource = SoftwareDependencyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
