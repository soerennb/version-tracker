<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\RuntimeSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

class RuntimeController extends Controller
{
    public function __invoke(RuntimeSettings $runtimeSettings): JsonResponse
    {
        $ttl = max($runtimeSettings->operations()->public_runtime_cache_ttl_seconds, 0);

        $runtime = $ttl > 0
            ? Cache::remember('public-runtime', $ttl, fn (): array => $runtimeSettings->publicRuntime())
            : $runtimeSettings->publicRuntime();

        return response()->json(['data' => $runtime]);
    }
}
