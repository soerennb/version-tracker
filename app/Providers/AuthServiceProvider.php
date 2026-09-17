<?php

namespace App\Providers;

use App\Models\AuditLog;
use App\Models\Deployment;
use App\Models\Environment;
use App\Models\FileAttachment;
use App\Models\ReleaseException;
use App\Models\Software;
use App\Models\TextContent;
use App\Models\User;
use App\Models\Version;
use App\Models\Vulnerability;
use App\Policies\AuditLogPolicy;
use App\Policies\DeploymentPolicy;
use App\Policies\EnvironmentPolicy;
use App\Policies\FileAttachmentPolicy;
use App\Policies\ReleaseExceptionPolicy;
use App\Policies\SoftwarePolicy;
use App\Policies\TextContentPolicy;
use App\Policies\UserPolicy;
use App\Policies\VersionPolicy;
use App\Policies\VulnerabilityPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Software::class => SoftwarePolicy::class,
        Version::class => VersionPolicy::class,
        FileAttachment::class => FileAttachmentPolicy::class,
        TextContent::class => TextContentPolicy::class,
        AuditLog::class => AuditLogPolicy::class,
        Deployment::class => DeploymentPolicy::class,
        Environment::class => EnvironmentPolicy::class,
        Vulnerability::class => VulnerabilityPolicy::class,
        User::class => UserPolicy::class,
        ReleaseException::class => ReleaseExceptionPolicy::class,
    ];

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        Gate::before(function (User $user): ?bool {
            if (! $user->isActive()) {
                return false;
            }

            return $user->isAdmin() ? true : null;
        });

        $abilities = config('authorization.abilities', []);

        foreach ($abilities as $ability) {
            Gate::define($ability, function (User $user) use ($ability): bool {
                return $user->hasAbility($ability);
            });
        }
    }
}
