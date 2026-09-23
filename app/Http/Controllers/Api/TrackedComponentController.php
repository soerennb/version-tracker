<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTrackedComponentRequest;
use App\Http\Requests\UpdateTrackedComponentRequest;
use App\Http\Resources\TrackedComponentResource;
use App\Models\TrackedComponent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrackedComponentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()?->can('view_versions'), 403);

        return TrackedComponentResource::collection(TrackedComponent::query()->with('versions')->orderBy('name')->paginate(100))->response();
    }

    public function store(StoreTrackedComponentRequest $request): JsonResponse
    {
        $data = $request->validated();
        if (($data['kind'] === 'customization') !== isset($data['customer_id'])) {
            return response()->json(['message' => 'Customization components require a customer; other components must be global.'], 422);
        }

        return TrackedComponentResource::make(TrackedComponent::query()->create($data))->response()->setStatusCode(201);
    }

    public function update(UpdateTrackedComponentRequest $request, TrackedComponent $trackedComponent): JsonResponse
    {
        $trackedComponent->update($request->validated());

        return TrackedComponentResource::make($trackedComponent->refresh())->response();
    }
}
