<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreReleaseCompositionRequest;
use App\Http\Resources\ReleaseCompositionResource;
use App\Models\Version;
use App\Services\ReleaseCompositionService;
use Illuminate\Http\JsonResponse;

class ReleaseCompositionController extends Controller
{
    public function show(Version $version): JsonResponse
    {
        $this->authorize('view', $version);

        return response()->json(['data' => $version->composition ? ReleaseCompositionResource::make($version->composition)->resolve() : null]);
    }

    public function update(StoreReleaseCompositionRequest $request, Version $version, ReleaseCompositionService $service): JsonResponse
    {
        $composition = $service->save($version, $request->validated());

        return ReleaseCompositionResource::make($composition)->response()->setStatusCode(200);
    }
}
