<?php

namespace App\Http\Middleware;

use App\Services\RuntimeSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplyRuntimeSettings
{
    public function __construct(private readonly RuntimeSettings $runtimeSettings) {}

    public function handle(Request $request, Closure $next): Response
    {
        $this->runtimeSettings->applyRequestSettings();

        return $next($request);
    }
}
