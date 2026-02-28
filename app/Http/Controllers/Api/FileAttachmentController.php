<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFileAttachmentRequest;
use App\Http\Requests\UpdateFileAttachmentRequest;
use App\Http\Resources\FileAttachmentResource;
use App\Models\FileAttachment;
use App\Models\Version;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class FileAttachmentController extends Controller
{
    public function index(Version $version): JsonResponse
    {
        $this->authorize('view', $version);

        $attachments = $version->fileAttachments()->latest()->paginate(25);

        return FileAttachmentResource::collection($attachments)->response();
    }

    public function store(StoreFileAttachmentRequest $request, Version $version): JsonResponse
    {
        $this->authorize('create', FileAttachment::class);

        $disk = config('filesystems.default', 'public');
        $uploadedFile = $request->file('file');
        $path = $uploadedFile->store("attachments/{$version->id}", $disk);

        $attachment = $version->fileAttachments()->create([
            'filename' => $this->sanitizeFilename($uploadedFile->getClientOriginalName()),
            'file_path' => $path,
            'mime_type' => $uploadedFile->getClientMimeType(),
            'size' => $uploadedFile->getSize(),
        ]);

        return FileAttachmentResource::make($attachment)
            ->response()
            ->setStatusCode(201);
    }

    public function show(Version $version, FileAttachment $fileAttachment): JsonResponse
    {
        $this->authorize('view', $fileAttachment);
        $this->ensureRelationship($version, $fileAttachment);

        return FileAttachmentResource::make($fileAttachment)->response();
    }

    public function update(UpdateFileAttachmentRequest $request, Version $version, FileAttachment $fileAttachment): JsonResponse
    {
        $this->authorize('update', $fileAttachment);
        $this->ensureRelationship($version, $fileAttachment);

        if ($request->hasFile('file')) {
            $disk = config('filesystems.default', 'public');
            Storage::disk($disk)->delete($fileAttachment->file_path);

            $uploadedFile = $request->file('file');
            $path = $uploadedFile->store("attachments/{$version->id}", $disk);

            $fileAttachment->update([
                'filename' => $this->sanitizeFilename($uploadedFile->getClientOriginalName()),
                'file_path' => $path,
                'mime_type' => $uploadedFile->getClientMimeType(),
                'size' => $uploadedFile->getSize(),
            ]);
        }

        return FileAttachmentResource::make($fileAttachment)->response();
    }

    public function destroy(Version $version, FileAttachment $fileAttachment): JsonResponse
    {
        $this->authorize('delete', $fileAttachment);
        $this->ensureRelationship($version, $fileAttachment);

        $disk = config('filesystems.default', 'public');
        Storage::disk($disk)->delete($fileAttachment->file_path);
        $fileAttachment->delete();

        return response()->json(status: 204);
    }

    protected function ensureRelationship(Version $version, FileAttachment $attachment): void
    {
        abort_if($attachment->version_id !== $version->id, 404);
    }

    protected function sanitizeFilename(string $originalName): string
    {
        $basename = pathinfo($originalName, PATHINFO_FILENAME);
        $extension = strtolower((string) pathinfo($originalName, PATHINFO_EXTENSION));

        $safeBase = preg_replace('/[^A-Za-z0-9._ -]/', '-', $basename) ?: 'file';
        $safeBase = trim((string) Str::of($safeBase)->squish()->limit(100, ''));

        return $extension !== '' ? $safeBase.'.'.$extension : $safeBase;
    }
}
