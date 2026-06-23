<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Software;
use App\Models\Version;
use App\Models\Vulnerability;
use App\Services\ImpactAnalysisService;
use Illuminate\Http\JsonResponse;

class ImpactAnalysisController extends Controller
{
    public function __construct(
        protected ImpactAnalysisService $impactAnalysisService,
    ) {}

    public function software(Software $software): JsonResponse
    {
        $this->authorize('view', $software);

        return response()->json([
            'data' => $this->impactAnalysisService->forSoftware($software),
        ]);
    }

    public function version(Version $version): JsonResponse
    {
        $this->authorize('view', $version);

        return response()->json([
            'data' => $this->impactAnalysisService->forVersion($version),
        ]);
    }

    public function vulnerability(Vulnerability $vulnerability): JsonResponse
    {
        $this->authorize('view', $vulnerability);

        return response()->json([
            'data' => $this->impactAnalysisService->forVulnerability($vulnerability),
        ]);
    }
}
