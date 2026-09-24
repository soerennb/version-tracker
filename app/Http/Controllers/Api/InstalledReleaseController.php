<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DeploymentResource;
use App\Models\Environment;
use App\Models\Software;
use App\Services\InstalledReleaseService;
use Illuminate\Http\JsonResponse;

class InstalledReleaseController extends Controller
{
    public function show(Environment $environment, Software $software, InstalledReleaseService $service): JsonResponse
    {
        abort_unless(auth()->user()?->can('view_deployments'), 403);

        $deployment = $service->current($environment, (int) $software->id);

        return response()->json(['data' => $deployment ? DeploymentResource::make($deployment)->resolve() : null]);
    }
}
