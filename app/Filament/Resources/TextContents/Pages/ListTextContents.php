<?php

namespace App\Filament\Resources\TextContents\Pages;

use App\Filament\Resources\TextContents\TextContentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListTextContents extends ListRecords
{
    protected static string $resource = TextContentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
