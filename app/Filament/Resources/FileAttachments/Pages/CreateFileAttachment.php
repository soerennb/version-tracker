<?php

namespace App\Filament\Resources\FileAttachments\Pages;

use App\Filament\Resources\FileAttachments\FileAttachmentResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Storage;

class CreateFileAttachment extends CreateRecord
{
    protected static string $resource = FileAttachmentResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $path = $data['file_path'] ?? null;

        if (! is_string($path) || $path === '') {
            return $data;
        }

        $disk = Storage::disk(config('filesystems.default', 'local'));

        $data['mime_type'] = $disk->mimeType($path) ?: 'application/octet-stream';
        $data['size'] = $disk->size($path);
        $data['filename'] ??= basename($path);

        return $data;
    }
}
