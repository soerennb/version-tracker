<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreComponentVersionRequest;
use App\Http\Requests\UpdateComponentVersionRequest;
use App\Http\Resources\ComponentVersionResource;
use App\Models\ComponentVersion;
use App\Models\TrackedComponent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ComponentVersionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('view_versions'), 403);

        return ComponentVersionResource::collection(ComponentVersion::query()
            ->with('component')
            ->when($request->integer('tracked_component_id'), fn ($query, int $id) => $query->where('tracked_component_id', $id))
            ->orderBy('tracked_component_id')->orderBy('version_label')->paginate(100))->response();
    }

    public function store(StoreComponentVersionRequest $request): JsonResponse
    {
        $data = $request->validated();
        $component = TrackedComponent::query()->findOrFail($data['tracked_component_id']);
        if ($component->kind === 'eforms_sdk' && preg_match('/^[0-9]+\.[0-9]+(?:\.[0-9]+)?$/', $data['version_label']) !== 1) {
            return response()->json(['message' => 'eForms SDK versions must use major.minor or major.minor.patch.'], 422);
        }

        return ComponentVersionResource::make(ComponentVersion::query()->create($data)->load('component'))->response()->setStatusCode(201);
    }

    public function update(UpdateComponentVersionRequest $request, ComponentVersion $componentVersion): JsonResponse
    {
        $componentVersion->update($request->validated());

        return ComponentVersionResource::make($componentVersion->load('component'))->response();
    }
}
