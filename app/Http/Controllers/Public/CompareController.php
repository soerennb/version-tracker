<?php

namespace App\Http\Controllers\Public;

use App\Enums\VersionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\PublicCompareRequest;
use App\Models\Version;
use App\Services\VersionComparisonService;
use Illuminate\Http\JsonResponse;

class CompareController extends Controller
{
    public function __invoke(PublicCompareRequest $request, VersionComparisonService $comparisonService): JsonResponse
    {
        $left = Version::query()->findOrFail($request->integer('left'));
        $right = Version::query()->findOrFail($request->integer('right'));

        abort_unless(
            $left->status === VersionStatus::PUBLISHED
            && $right->status === VersionStatus::PUBLISHED
            && $left->software_id === $right->software_id,
            404,
        );

        return response()->json(['data' => $comparisonService->compare($left, $right)]);
    }
}
