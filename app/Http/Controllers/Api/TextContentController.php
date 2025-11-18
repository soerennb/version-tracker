<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTextContentRequest;
use App\Http\Requests\UpdateTextContentRequest;
use App\Http\Resources\TextContentResource;
use App\Models\TextContent;
use App\Models\Version;
use Illuminate\Http\JsonResponse;

class TextContentController extends Controller
{
    public function index(Version $version): JsonResponse
    {
        $this->authorize('view', $version);

        $contents = $version->textContents()->latest()->paginate(25);

        return TextContentResource::collection($contents)->response();
    }

    public function store(StoreTextContentRequest $request, Version $version): JsonResponse
    {
        $this->authorize('create', TextContent::class);

        $content = $version->textContents()->create($request->validated());

        return TextContentResource::make($content)
            ->response()
            ->setStatusCode(201);
    }

    public function show(Version $version, TextContent $textContent): JsonResponse
    {
        $this->authorize('view', $textContent);
        $this->ensureRelationship($version, $textContent);

        return TextContentResource::make($textContent)->response();
    }

    public function update(UpdateTextContentRequest $request, Version $version, TextContent $textContent): JsonResponse
    {
        $this->authorize('update', $textContent);
        $this->ensureRelationship($version, $textContent);

        $textContent->update($request->validated());

        return TextContentResource::make($textContent)->response();
    }

    public function destroy(Version $version, TextContent $textContent): JsonResponse
    {
        $this->authorize('delete', $textContent);
        $this->ensureRelationship($version, $textContent);

        $textContent->delete();

        return response()->json(status: 204);
    }

    protected function ensureRelationship(Version $version, TextContent $textContent): void
    {
        abort_if($textContent->version_id !== $version->id, 404);
    }
}
