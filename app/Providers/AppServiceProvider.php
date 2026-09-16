<?php

namespace App\Providers;

use App\Listeners\RecordLastLogin;
use App\Models\Software;
use App\Models\TextContent;
use App\Models\User;
use App\Models\Version;
use App\Models\VersionReview;
use App\Observers\SoftwareObserver;
use App\Observers\TextContentObserver;
use App\Observers\VersionObserver;
use App\Observers\VersionReviewObserver;
use App\Services\RuntimeSettings;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(RuntimeSettings::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(Login::class, RecordLastLogin::class);

        Queue::before(function (JobProcessing $event): void {
            app(RuntimeSettings::class)->applyRequestSettings();
        });

        RateLimiter::for('api', function (Request $request): Limit {
            return Limit::perMinute(app(RuntimeSettings::class)->security()->api_rate_limit_per_minute)
                ->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('auth', function (Request $request): Limit {
            return Limit::perMinute(app(RuntimeSettings::class)->access()->auth_rate_limit_per_minute)
                ->by(Str::lower((string) $request->input('email')).'|'.$request->ip());
        });

        RateLimiter::for('verification', function (Request $request): Limit {
            return Limit::perMinute(app(RuntimeSettings::class)->access()->verification_rate_limit_per_minute)
                ->by($request->user()?->id ?: $request->ip());
        });

        VerifyEmail::createUrlUsing(function (User $user): string {
            $expiresAt = now()->addMinutes(app(RuntimeSettings::class)->access()->email_verification_expire_minutes);

            return URL::temporarySignedRoute(
                'verification.verify',
                $expiresAt,
                [
                    'id' => $user->getKey(),
                    'hash' => sha1($user->getEmailForVerification()),
                ],
            );
        });

        ResetPassword::createUrlUsing(function (User $user, string $token): string {
            return url('/account/reset-password?token='.urlencode($token).'&email='.urlencode($user->getEmailForPasswordReset()));
        });

        Software::observe(SoftwareObserver::class);
        TextContent::observe(TextContentObserver::class);
        Version::observe(VersionObserver::class);
        VersionReview::observe(VersionReviewObserver::class);
    }
}
