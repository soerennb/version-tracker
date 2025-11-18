<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSoftwareRequest;
use App\Http\Requests\UpdateSoftwareRequest;
use App\Http\Resources\SoftwareResource;
use App\Http\Resources\VersionResource;
use App\Models\Software;
use App\Services\SoftwareService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SoftwareController extends Controller
{
    public function __construct(
        protected SoftwareService $softwareService,
    ) {
        $this->authorizeResource(Software::class, 'software');
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->integer('per_page', 15);
        $perPage = min(max($perPage, 1), 100);

        $allowedSorts = ['name', 'created_at', 'status', 'last_release_date'];
        $sort = in_array($request->get('sort'), $allowedSorts, true) ? $request->get('sort') : 'name';
        $direction = strtolower((string) $request->get('direction', 'asc'));
        $direction = in_array($direction, ['asc', 'desc'], true) ? $direction : 'asc';

        $softwares = Software::query()
            ->with(['creator'])
            ->withCount('versions')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('compliance'), fn ($query) => $query->where('compliance_status', $request->string('compliance')->toString()))
            ->when($search = $request->string('search')->toString(), function ($query) use ($search) {
                $query->where(fn ($q) => $q
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%"));
            })
            ->orderBy($sort, $direction)
            ->paginate($perPage)
            ->appends($request->query());

        return SoftwareResource::collection($softwares)->response();
    }

    public function store(StoreSoftwareRequest $request): JsonResponse
    {
        $software = $this->softwareService->create($request->validated());

        return SoftwareResource::make($software->load('creator'))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Software $software): JsonResponse
    {
        $software->load([
            'creator',
            'versions' => fn ($query) => $query->latest('release_date'),
            'dependenciesOutgoing.dependsOnSoftware',
            'dependenciesIncoming.software',
        ]);

        return SoftwareResource::make($software)->response();
    }

    public function update(UpdateSoftwareRequest $request, Software $software): JsonResponse
    {
        $software = $this->softwareService->update($software, $request->validated());

        return SoftwareResource::make($software->load('creator'))->response();
    }

    public function destroy(Software $software): JsonResponse
    {
        $software->delete();

        return response()->json(status: 204);
    }

    public function versions(Software $software): JsonResponse
    {
        $this->authorize('view', $software);

        $versions = $software->versions()
            ->with(['textContents', 'fileAttachments'])
            ->latest('release_date')
            ->paginate(25);

        return VersionResource::collection($versions)->response();
    }
}
