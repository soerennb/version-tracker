<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RejectVersionRequest;
use App\Http\Requests\StoreVersionRequest;
use App\Http\Requests\UpdateVersionRequest;
use App\Http\Resources\VersionResource;
use App\Models\Version;
use App\Services\VersionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VersionController extends Controller
{
    public function __construct(
        protected VersionService $versionService,
    ) {
        $this->authorizeResource(Version::class, 'version');
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->integer('per_page', 15), 1), 100);

        $versions = Version::query()
            ->with(['software'])
            ->when($request->filled('software_id'), fn ($query) => $query->where('software_id', $request->integer('software_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('approval_status'), fn ($query) => $query->where('approval_status', $request->string('approval_status')->toString()))
            ->when($request->filled('search'), fn ($query) => $query->where('version_number', 'like', '%'.$request->string('search')->toString().'%'))
            ->latest('release_date')
            ->paginate($perPage)
            ->appends($request->query());

        return VersionResource::collection($versions)->response();
    }

    public function store(StoreVersionRequest $request): JsonResponse
    {
        $version = $this->versionService->create($request->validated());

        return VersionResource::make($version->load('software'))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Version $version): JsonResponse
    {
        $version->load(['software', 'textContents', 'fileAttachments', 'vulnerabilities']);

        return VersionResource::make($version)->response();
    }

    public function update(UpdateVersionRequest $request, Version $version): JsonResponse
    {
        $version = $this->versionService->update($version, $request->validated());

        return VersionResource::make($version->load('software'))->response();
    }

    public function destroy(Version $version): JsonResponse
    {
        $version->delete();

        return response()->json(status: 204);
    }

    public function approve(Version $version): JsonResponse
    {
        $this->authorize('approve', $version);

        $approved = $this->versionService->approve($version);

        return VersionResource::make($approved)->response();
    }

    public function reject(RejectVersionRequest $request, Version $version): JsonResponse
    {
        $data = $request->validated();

        $rejected = $this->versionService->reject($version, $data['reason'], $data['reject_reason']);

        return VersionResource::make($rejected)->response();
    }
}
