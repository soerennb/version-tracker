<?php

namespace App\Http\Middleware;

use App\Services\RuntimeSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PublicFeature
{
    public function __construct(private readonly RuntimeSettings $runtimeSettings) {}

    public function handle(Request $request, Closure $next, string $feature): Response
    {
        abort_unless($this->runtimeSettings->publicFeatureEnabled($feature), 404);

        return $next($request);
    }
}
