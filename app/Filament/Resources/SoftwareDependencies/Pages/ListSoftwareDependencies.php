<?php

namespace App\Filament\Resources\SoftwareDependencies\Pages;

use App\Filament\Resources\SoftwareDependencies\SoftwareDependencyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSoftwareDependencies extends ListRecords
{
    protected static string $resource = SoftwareDependencyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
