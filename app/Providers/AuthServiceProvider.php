<?php

namespace App\Providers;

use App\Models\AuditLog;
use App\Models\FileAttachment;
use App\Models\Software;
use App\Models\TextContent;
use App\Models\User;
use App\Models\Version;
use App\Models\Vulnerability;
use App\Policies\AuditLogPolicy;
use App\Policies\FileAttachmentPolicy;
use App\Policies\SoftwarePolicy;
use App\Policies\TextContentPolicy;
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
        Vulnerability::class => VulnerabilityPolicy::class,
    ];

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        $abilities = [
            'create_software',
            'edit_software',
            'delete_software',
            'manage_dependencies',
            'create_versions',
            'edit_versions',
            'delete_versions',
            'approve_versions',
            'publish_versions',
            'create_content',
            'edit_content',
            'delete_content',
            'upload_files',
            'edit_files',
            'delete_files',
            'download_files',
            'view_audit_logs',
            'export_data',
            'view_vulnerabilities',
            'create_vulnerabilities',
            'edit_vulnerabilities',
            'delete_vulnerabilities',
        ];

        foreach ($abilities as $ability) {
            Gate::define($ability, function (User $user) {
                // Placeholder for future role/permission checks.
                return true;
            });
        }
    }
}
