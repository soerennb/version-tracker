<?php

namespace App\Services;

use App\Helpers\AuditHelper;
use App\Models\FileAttachment;
use App\Models\Version;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class FileAttachmentService
{
    /** @param array<string,mixed> $metadata */
    public function store(Version $version, UploadedFile $file, array $metadata, ?FileAttachment $attachment = null): FileAttachment
    {
        $disk = config('filesystems.default');
        $path = $file->store('attachments/'.$version->id, $disk);
        if (! $path) {
            throw new RuntimeException('The attachment could not be stored.');
        }
        $oldPath = $attachment?->file_path;
        try {
            $attachment = (new FileAttachment)->getConnection()->transaction(function () use ($version, $file, $metadata, $attachment, $path): FileAttachment {
                $data = [...$metadata, 'filename' => $this->sanitizeFilename($file->getClientOriginalName()), 'file_path' => $path, 'mime_type' => $file->getMimeType(), 'size' => $file->getSize()];
                if ($attachment) {
                    $this->update($attachment, $data);
                } else {
                    $attachment = $version->fileAttachments()->create($data);
                    AuditHelper::logAction(auth()->user(), 'file_attachment.created', FileAttachment::class, (int) $attachment->id, [], $attachment->toArray());
                }

                return $attachment;
            });
        } catch (\Throwable $exception) {
            Storage::disk($disk)->delete($path);
            throw $exception;
        }
        if ($oldPath && $oldPath !== $path) {
            if ($attachment->getConnection()->transactionLevel() > 0) {
                $attachment->getConnection()->afterCommit(fn () => Storage::disk($disk)->delete($oldPath));
            } else {
                Storage::disk($disk)->delete($oldPath);
            }
        }

        return $attachment->refresh();
    }

    /** @param array<string,mixed> $metadata */
    public function update(FileAttachment $attachment, array $metadata): FileAttachment
    {
        $before = $attachment->toArray();
        $attachment->fill($metadata)->save();
        AuditHelper::logAction(auth()->user(), 'file_attachment.updated', FileAttachment::class, (int) $attachment->id, $before, $attachment->toArray());

        return $attachment;
    }

    public function delete(FileAttachment $attachment): void
    {
        $before = $attachment->toArray();
        $path = $attachment->file_path;
        $attachment->delete();
        Storage::disk(config('filesystems.default'))->delete($path);
        AuditHelper::logAction(auth()->user(), 'file_attachment.deleted', FileAttachment::class, (int) $attachment->id, $before, []);
    }

    public function sanitizeFilename(string $originalName): string
    {
        $basename = pathinfo($originalName, PATHINFO_FILENAME);
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $safeBase = preg_replace('/[^A-Za-z0-9._ -]/', '-', $basename) ?: 'file';
        $safeBase = trim((string) Str::of($safeBase)->squish()->limit(100, ''));

        return $extension !== '' ? $safeBase.'.'.$extension : $safeBase;
    }
}
