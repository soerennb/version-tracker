<?php

namespace App\Services;

use App\Http\Requests\StoreFileAttachmentRequest;
use App\Http\Requests\UpdateFileAttachmentRequest;
use App\Models\AttachmentUpload;
use App\Models\FileAttachment;
use App\Models\User;
use App\Models\Version;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AttachmentUploadService
{
    public function __construct(protected ApiTokenService $tokens, protected FileAttachmentService $files, protected ContentValidation $validation) {}

    /** @param array<string,mixed> $data */
    public function prepare(User $user, array $data): AttachmentUpload
    {
        $data = Validator::make($data, ['version_id' => ['required', 'integer'], 'filename' => ['required', 'string', 'max:255'], 'attachment_id' => ['nullable', 'integer'], 'metadata' => ['sometimes', 'array']])->validate();
        $version = Version::query()->findOrFail($data['version_id']);
        Gate::authorize('view', $version);
        $attachment = isset($data['attachment_id']) ? FileAttachment::query()->findOrFail($data['attachment_id']) : null;
        $this->authorizeTarget($user, $version, $attachment);

        return AttachmentUpload::query()->create(['user_id' => $user->id, 'personal_access_token_id' => $user->currentAccessToken()->id, 'version_id' => $version->id, 'replace_attachment_id' => $attachment?->id, 'filename' => $this->files->sanitizeFilename($data['filename']), 'metadata' => $data['metadata'] ?? [], 'expires_at' => now()->addMinutes(15)]);
    }

    public function transfer(User $user, AttachmentUpload $upload, UploadedFile $file): void
    {
        $upload->getConnection()->transaction(function () use ($user, $upload, $file): void {
            $locked = AttachmentUpload::query()->lockForUpdate()->findOrFail($upload->id);
            $this->authorizeUpload($user, $locked);
            if ($locked->temporary_path || $locked->completed_at) {
                throw ValidationException::withMessages(['upload' => 'This upload has already received a file.']);
            }
            $settings = app(RuntimeSettings::class)->security();
            Validator::make(['file' => $file], ['file' => ['required', 'file', 'max:'.$settings->upload_max_kb, 'mimes:'.implode(',', $settings->upload_allowed_extensions)]])->validate();
            $path = $file->storeAs('mcp-uploads/'.$locked->id, $locked->filename, 'local');
            if (! $path) {
                throw new \RuntimeException('The upload could not be stored.');
            }
            try {
                $locked->update(['temporary_path' => $path, 'mime_type' => $file->getMimeType(), 'size' => $file->getSize()]);
            } catch (\Throwable $exception) {
                Storage::disk('local')->delete($path);
                throw $exception;
            }
        });
    }

    public function complete(User $user, string $id): FileAttachment
    {
        $path = null;
        $newPath = null;
        try {
            $result = (new AttachmentUpload)->getConnection()->transaction(function () use ($user, $id, &$path, &$newPath): FileAttachment {
                $upload = AttachmentUpload::query()->lockForUpdate()->findOrFail($id);
                $this->authorizeUpload($user, $upload);
                if ($upload->completed_at) {
                    return FileAttachment::query()->findOrFail($upload->result_attachment_id);
                }
                if (! $upload->temporary_path || ! Storage::disk('local')->exists($upload->temporary_path)) {
                    throw ValidationException::withMessages(['upload' => 'Upload the file before completing this operation.']);
                }
                $attachment = $upload->replace_attachment_id ? FileAttachment::query()->findOrFail($upload->replace_attachment_id) : null;
                $file = new UploadedFile(Storage::disk('local')->path($upload->temporary_path), $upload->filename, $upload->mime_type, null, true);
                $requestClass = $attachment ? UpdateFileAttachmentRequest::class : StoreFileAttachmentRequest::class;
                $data = $this->validation->validate($requestClass, [...$upload->metadata, 'file' => $file], ['version' => $upload->version, 'file_attachment' => $attachment]);
                unset($data['file']);
                $result = $this->files->store($upload->version, $file, $data, $attachment);
                $newPath = $result->file_path;
                $upload->update(['completed_at' => now(), 'result_attachment_id' => $result->id]);
                $path = $upload->temporary_path;

                return $result;
            });
        } catch (\Throwable $exception) {
            if ($newPath) {
                Storage::disk(config('filesystems.default'))->delete($newPath);
            }
            throw $exception;
        }
        if ($path) {
            Storage::disk('local')->delete($path);
        }

        return $result;
    }

    public function prune(): void
    {
        AttachmentUpload::query()->where('expires_at', '<=', now())->chunkById(100, function ($uploads): void {
            foreach ($uploads as $upload) {
                $upload->getConnection()->transaction(function () use ($upload): void {
                    $locked = AttachmentUpload::query()->lockForUpdate()->find($upload->id);
                    if ($locked) {
                        Storage::disk('local')->deleteDirectory('mcp-uploads/'.$locked->id);
                        $locked->delete();
                    }
                });
            }
        });
        foreach (Storage::disk('local')->directories('mcp-uploads') as $directory) {
            if (! AttachmentUpload::query()->whereKey(basename($directory))->exists()) {
                Storage::disk('local')->deleteDirectory($directory);
            }
        }
    }

    protected function authorizeUpload(User $user, AttachmentUpload $upload): void
    {
        if ($upload->user_id !== $user->id || $upload->personal_access_token_id !== $user->currentAccessToken()?->id) {
            throw new AuthorizationException('This upload belongs to another token.');
        }
        if ($upload->expires_at->isPast()) {
            throw ValidationException::withMessages(['upload' => 'This upload has expired. Prepare another upload.']);
        }
        $this->authorizeTarget($user, $upload->version, $upload->replace_attachment_id ? FileAttachment::query()->findOrFail($upload->replace_attachment_id) : null);
    }

    protected function authorizeTarget(User $user, Version $version, ?FileAttachment $attachment): void
    {
        $this->tokens->authorize($user, 'access_mcp');
        $this->tokens->authorize($user, $attachment ? 'edit_files' : 'upload_files');
        Gate::authorize('view', $version);
        if ($attachment && $attachment->version_id !== $version->id) {
            throw ValidationException::withMessages(['attachment_id' => 'The attachment belongs to a different version.']);
        }
        Gate::authorize($attachment ? 'update' : 'create', $attachment ?? FileAttachment::class);
    }
}
