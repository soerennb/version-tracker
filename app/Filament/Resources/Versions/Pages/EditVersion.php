<?php

namespace App\Filament\Resources\Versions\Pages;

use App\Filament\Resources\Versions\VersionResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditVersion extends EditRecord
{
    protected static string $resource = VersionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('releaseNotes')
                ->label(__('filament.release_notes.title_short'))
                ->icon('heroicon-o-document-text')
                ->url(fn (): string => VersionResource::getUrl('release-notes', ['record' => $this->record])),
            DeleteAction::make(),
        ];
    }
}
