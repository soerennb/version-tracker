<?php

namespace App\Providers;

use App\Models\Software;
use App\Models\TextContent;
use App\Models\Version;
use App\Models\VersionReview;
use App\Observers\SoftwareObserver;
use App\Observers\TextContentObserver;
use App\Observers\VersionObserver;
use App\Observers\VersionReviewObserver;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        RateLimiter::for('api', function (Request $request): Limit {
            return Limit::perMinute((int) config('security.api_rate_limit_per_minute', 60))
                ->by($request->user()?->id ?: $request->ip());
        });

        Software::observe(SoftwareObserver::class);
        TextContent::observe(TextContentObserver::class);
        Version::observe(VersionObserver::class);
        VersionReview::observe(VersionReviewObserver::class);
    }
}
