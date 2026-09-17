<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEnvironmentRequest;
use App\Http\Requests\UpdateEnvironmentRequest;
use App\Http\Resources\EnvironmentResource;
use App\Models\Environment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EnvironmentController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Environment::class, 'environment');
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->integer('per_page', 25), 1), 100);

        $environments = Environment::query()
            ->with('latestSuccessfulDeployment.version')
            ->when($request->filled('is_active'), fn ($query) => $query->where('is_active', $request->boolean('is_active')))
            ->when($request->filled('is_production'), fn ($query) => $query->where('is_production', $request->boolean('is_production')))
            ->when($search = $request->string('search')->toString(), fn ($query) => $query->where(fn ($query) => $query
                ->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%")))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate($perPage)
            ->appends($request->query());

        return EnvironmentResource::collection($environments)->response();
    }

    public function store(StoreEnvironmentRequest $request): JsonResponse
    {
        $environment = Environment::query()->create($request->validated());

        return EnvironmentResource::make($environment)
            ->response()
            ->setStatusCode(201);
    }

    public function show(Environment $environment): JsonResponse
    {
        return EnvironmentResource::make($environment->load('latestSuccessfulDeployment.version'))->response();
    }

    public function update(UpdateEnvironmentRequest $request, Environment $environment): JsonResponse
    {
        $environment->forceFill($request->validated())->save();

        return EnvironmentResource::make($environment->refresh())->response();
    }
}
