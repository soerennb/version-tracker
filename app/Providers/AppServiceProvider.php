<?php

namespace App\Providers;

use App\Models\Software;
use App\Models\Version;
use App\Observers\SoftwareObserver;
use App\Observers\VersionObserver;
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
        Software::observe(SoftwareObserver::class);
        Version::observe(VersionObserver::class);
    }
}
