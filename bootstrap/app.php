<?php

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $trustedHosts = array_values(array_filter(array_map(
            static fn (string $host): string => trim($host),
            explode(',', (string) env('TRUSTED_HOSTS', ''))
        )));
        if (! empty($trustedHosts)) {
            $middleware->trustHosts(at: $trustedHosts, subdomains: false);
        }

        $trustedProxiesValue = trim((string) env('TRUSTED_PROXIES', ''));
        $trustedProxies = null;
        if ($trustedProxiesValue === '*') {
            $trustedProxies = '*';
        } elseif ($trustedProxiesValue !== '') {
            $trustedProxies = array_values(array_filter(array_map(
                static fn (string $proxy): string => trim($proxy),
                explode(',', $trustedProxiesValue)
            )));
        }

        if ($trustedProxies !== null) {
            $middleware->trustProxies(at: $trustedProxies);
        }

        $middleware->append(SecurityHeaders::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
