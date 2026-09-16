<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSoftwareDependencyRequest;
use App\Http\Requests\UpdateSoftwareDependencyRequest;
use App\Http\Resources\SoftwareDependencyResource;
use App\Models\SoftwareDependency;
use App\Services\ContentOperations;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class SoftwareDependencyController extends Controller
{
    public function index(): JsonResponse
    {
        Gate::authorize('manage_dependencies');

        $dependencies = SoftwareDependency::query()
            ->with(['software', 'dependsOnSoftware', 'appliesToVersion', 'minVersion', 'maxVersion'])
            ->paginate(25);

        return SoftwareDependencyResource::collection($dependencies)->response();
    }

    public function store(StoreSoftwareDependencyRequest $request): JsonResponse
    {
        Gate::authorize('manage_dependencies');

        try {
            $dependency = app(ContentOperations::class)->create(SoftwareDependency::class, $request->validated());
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'scope_key' => __('validation.custom.software_dependency.duplicate'),
            ]);
        }

        return SoftwareDependencyResource::make($dependency->load(['software', 'dependsOnSoftware']))
            ->response()
            ->setStatusCode(201);
    }

    public function show(SoftwareDependency $softwareDependency): JsonResponse
    {
        Gate::authorize('manage_dependencies');

        return SoftwareDependencyResource::make($softwareDependency->load(['software', 'dependsOnSoftware']))
            ->response();
    }

    public function update(UpdateSoftwareDependencyRequest $request, SoftwareDependency $softwareDependency): JsonResponse
    {
        Gate::authorize('manage_dependencies');

        try {
            app(ContentOperations::class)->update($softwareDependency, $request->validated());
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages([
                'scope_key' => __('validation.custom.software_dependency.duplicate'),
            ]);
        }

        return SoftwareDependencyResource::make($softwareDependency->load(['software', 'dependsOnSoftware']))
            ->response();
    }

    public function destroy(SoftwareDependency $softwareDependency): JsonResponse
    {
        Gate::authorize('manage_dependencies');

        app(ContentOperations::class)->delete($softwareDependency);

        return response()->json(status: 204);
    }
}
