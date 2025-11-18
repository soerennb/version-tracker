<?php

namespace App\Filament\Resources\TextContents\Pages;

use App\Filament\Resources\TextContents\TextContentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditTextContent extends EditRecord
{
    protected static string $resource = TextContentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
