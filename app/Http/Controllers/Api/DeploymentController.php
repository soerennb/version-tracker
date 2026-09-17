<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeploymentCompletionRequest;
use App\Http\Requests\DeploymentCorrectionRequest;
use App\Http\Requests\DeploymentTransitionRequest;
use App\Http\Requests\RollbackDeploymentRequest;
use App\Http\Requests\StoreDeploymentRequest;
use App\Http\Requests\UpdateDeploymentRequest;
use App\Http\Resources\DeploymentResource;
use App\Models\Deployment;
use App\Services\DeploymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeploymentController extends Controller
{
    public function __construct(protected DeploymentService $deploymentService)
    {
        $this->authorizeResource(Deployment::class, 'deployment');
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->integer('per_page', 25), 1), 100);

        $deployments = Deployment::query()
            ->with(['software', 'version', 'environment', 'creator', 'approver', 'executor'])
            ->when($request->filled('software_id'), fn ($query) => $query->where('software_id', $request->integer('software_id')))
            ->when($request->filled('version_id'), fn ($query) => $query->where('version_id', $request->integer('version_id')))
            ->when($request->filled('environment_id'), fn ($query) => $query->where('environment_id', $request->integer('environment_id')))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('date_from'), fn ($query) => $query->whereDate('created_at', '>=', $request->date('date_from')))
            ->when($request->filled('date_to'), fn ($query) => $query->whereDate('created_at', '<=', $request->date('date_to')))
            ->latest('created_at')
            ->paginate($perPage)
            ->appends($request->query());

        return DeploymentResource::collection($deployments)->response();
    }

    public function store(StoreDeploymentRequest $request): JsonResponse
    {
        $result = $this->deploymentService->create($request->validated(), $request->user());

        return DeploymentResource::make($result['deployment'])
            ->response()
            ->setStatusCode($result['created'] ? 201 : 200);
    }

    public function show(Deployment $deployment): JsonResponse
    {
        return DeploymentResource::make($deployment->load([
            'software',
            'version',
            'environment',
            'creator',
            'approver',
            'executor',
            'relatedDeployment.version',
            'events.actor',
        ]))->response();
    }

    public function update(UpdateDeploymentRequest $request, Deployment $deployment): JsonResponse
    {
        $updated = $this->deploymentService->update($deployment, $request->validated(), $request->user());

        return DeploymentResource::make($updated)->response();
    }

    public function approve(DeploymentTransitionRequest $request, Deployment $deployment): JsonResponse
    {
        $this->authorize('approve', $deployment);

        return DeploymentResource::make($this->deploymentService->approve($deployment, $request->string('comment')->toString() ?: null, $request->user()))->response();
    }

    public function start(DeploymentTransitionRequest $request, Deployment $deployment): JsonResponse
    {
        $this->authorize('start', $deployment);

        return DeploymentResource::make($this->deploymentService->start($deployment, $request->string('comment')->toString() ?: null, $request->user()))->response();
    }

    public function succeed(DeploymentCompletionRequest $request, Deployment $deployment): JsonResponse
    {
        $this->authorize('complete', $deployment);

        return DeploymentResource::make($this->deploymentService->succeed($deployment, $request->string('result')->toString(), $request->user()))->response();
    }

    public function fail(DeploymentCompletionRequest $request, Deployment $deployment): JsonResponse
    {
        $this->authorize('complete', $deployment);

        return DeploymentResource::make($this->deploymentService->fail($deployment, $request->string('result')->toString(), $request->user()))->response();
    }

    public function cancel(DeploymentTransitionRequest $request, Deployment $deployment): JsonResponse
    {
        $this->authorize('cancel', $deployment);

        return DeploymentResource::make($this->deploymentService->cancel($deployment, $request->string('comment')->toString(), $request->user()))->response();
    }

    public function rollback(RollbackDeploymentRequest $request, Deployment $deployment): JsonResponse
    {
        $this->authorize('rollback', $deployment);

        return DeploymentResource::make($this->deploymentService->rollback($deployment, $request->validated(), $request->user()))
            ->response()
            ->setStatusCode(201);
    }

    public function correct(DeploymentCorrectionRequest $request, Deployment $deployment): JsonResponse
    {
        $this->authorize('correct', $deployment);

        return DeploymentResource::make($this->deploymentService->correct(
            $deployment,
            $request->array('changes'),
            $request->string('reason')->toString(),
            $request->user(),
        ))->response();
    }
}
