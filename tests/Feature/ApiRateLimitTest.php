<?php

namespace Tests\Feature;

use Illuminate\Routing\Route as IlluminateRoute;
use Tests\TestCase;

class ApiRateLimitTest extends TestCase
{
    public function test_public_api_timeline_route_has_api_throttle_middleware(): void
    {
        $route = collect(app('router')->getRoutes()->getRoutes())
            ->first(fn (IlluminateRoute $candidate) => $candidate->uri() === 'api/public/timeline');

        $this->assertNotNull($route);
        $this->assertContains('throttle:api', $route->gatherMiddleware());
    }

    public function test_authenticated_api_routes_have_api_throttle_middleware(): void
    {
        $route = collect(app('router')->getRoutes()->getRoutes())
            ->first(fn (IlluminateRoute $candidate) => $candidate->uri() === 'api/softwares');

        $this->assertNotNull($route);
        $this->assertContains('throttle:api', $route->gatherMiddleware());
    }
}
