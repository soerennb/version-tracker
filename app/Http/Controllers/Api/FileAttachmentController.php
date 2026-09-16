<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFileAttachmentRequest;
use App\Http\Requests\UpdateFileAttachmentRequest;
use App\Http\Resources\FileAttachmentResource;
use App\Models\FileAttachment;
use App\Models\Version;
use App\Services\FileAttachmentService;
use Illuminate\Http\JsonResponse;

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

        $attachment = app(FileAttachmentService::class)->store($version, $request->file('file'), $request->safe()->except('file'));

        return FileAttachmentResource::make($attachment)
            ->response()
            ->setStatusCode(201);
    }

    public function show(FileAttachment $fileAttachment): JsonResponse
    {
        $this->authorize('view', $fileAttachment);

        return FileAttachmentResource::make($fileAttachment)->response();
    }

    public function update(UpdateFileAttachmentRequest $request, FileAttachment $fileAttachment): JsonResponse
    {
        $this->authorize('update', $fileAttachment);

        $service = app(FileAttachmentService::class);
        if ($request->hasFile('file')) {
            $service->store($fileAttachment->version, $request->file('file'), $request->safe()->except('file'), $fileAttachment);
        } else {
            $service->update($fileAttachment, $request->safe()->except('file'));
        }

        return FileAttachmentResource::make($fileAttachment)->response();
    }

    public function destroy(FileAttachment $fileAttachment): JsonResponse
    {
        $this->authorize('delete', $fileAttachment);

        app(FileAttachmentService::class)->delete($fileAttachment);

        return response()->json(status: 204);
    }
}
