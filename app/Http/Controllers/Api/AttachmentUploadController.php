<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\TransferAttachmentUploadRequest;
use App\Models\AttachmentUpload;
use App\Services\AttachmentUploadService;
use Illuminate\Http\JsonResponse;

class AttachmentUploadController extends Controller
{
    public function __invoke(TransferAttachmentUploadRequest $request, AttachmentUpload $upload, AttachmentUploadService $service): JsonResponse
    {
        $service->transfer($request->user(), $upload, $request->file('file'));

        return response()->json(['upload_id' => $upload->id, 'uploaded' => true]);
    }
}
