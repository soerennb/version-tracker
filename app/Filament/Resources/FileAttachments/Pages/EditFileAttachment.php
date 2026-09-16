<?php

namespace App\Filament\Resources\FileAttachments\Pages;

use App\Filament\Resources\FileAttachments\FileAttachmentResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

class EditFileAttachment extends EditRecord
{
    protected static string $resource = FileAttachmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $path = $data['file_path'] ?? null;

        if (! is_string($path) || $path === '' || $path === $this->record->file_path) {
            return $data;
        }

        $disk = Storage::disk(config('filesystems.default', 'local'));

        $data['mime_type'] = $disk->mimeType($path) ?: 'application/octet-stream';
        $data['size'] = $disk->size($path);

        return $data;
    }
}
