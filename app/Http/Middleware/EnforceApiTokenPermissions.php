<?php

namespace App\Http\Middleware;

use App\Services\ApiTokenService;
use App\Services\RuntimeSettings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceApiTokenPermissions
{
    public function handle(Request $request, Closure $next, string $access = 'rest'): Response
    {
        $user = $request->user();

        if (! $request->bearerToken() && $access === 'rest') {
            abort_if($user && ! $user->isActive(), 403);

            return $next($request);
        }

        abort_unless($request->bearerToken() && $user, 401);
        abort_unless($user->isActive(), 403);
        $tokens = app(ApiTokenService::class);
        $tokens->authorize($user, 'access_'.$access);
        if (app(RuntimeSettings::class)->access()->email_verification_required) {
            abort_unless($user->hasVerifiedEmail(), 403);
        }
        if ($access === 'rest') {
            $route = $request->route();
            $controller = class_basename($route->getControllerClass());
            $action = $route->getActionMethod();
            $entities = ['SoftwareController' => 'software', 'VersionController' => 'versions', 'TextContentController' => 'content', 'FileAttachmentController' => 'files', 'SoftwareDependencyController' => 'dependencies', 'VulnerabilityController' => 'vulnerabilities', 'EnvironmentController' => 'environments', 'DeploymentController' => 'deployments'];
            if (isset($entities[$controller])) {
                $entity = $entities[$controller];
                $ability = match ($action) {
                    'index','show','versions' => 'view_'.$entity,'store' => 'create_'.$entity,'update' => 'edit_'.$entity,'destroy' => 'delete_'.$entity,'approve','reject' => 'approve_versions','publish' => 'publish_versions',default => null
                };
                if ($entity === 'files') {
                    $ability = match ($action) {
                        'index','show' => 'download_files','store' => 'upload_files','update' => 'edit_files','destroy' => 'delete_files',default => null
                    };
                }
                if ($entity === 'environments') {
                    $ability = match ($action) {
                        'index', 'show' => 'view_environments',
                        'store', 'update', 'destroy' => 'manage_environments',
                        default => null,
                    };
                }
                if ($entity === 'deployments') {
                    $ability = match ($action) {
                        'index', 'show' => 'view_deployments',
                        'store' => 'create_deployments',
                        'update' => 'create_deployments',
                        'approve' => 'approve_deployments',
                        'start', 'succeed', 'fail', 'rollback', 'cancel' => 'execute_deployments',
                        'correct' => 'correct_deployments',
                        default => null,
                    };
                }
            } else {
                $ability = match ($controller) {
                    'AuditLogController' => 'view_audit_logs','ExportController' => match ($action) {
                        'compliance' => 'export_compliance',
                        'deploymentsCsv' => 'export_deployments',
                        default => 'export_data',
                    },'ImpactAnalysisController' => match ($action) {
                        'software' => 'view_software','version' => 'view_versions',default => 'view_vulnerabilities'
                    },'SbomController' => match ($action) {
                        'index', 'show' => 'view_sboms',
                        'exceptions', 'readiness' => 'view_readiness',
                        'store' => 'upload_sboms',
                        'enrich' => 'manage_feeds',
                        'storeException', 'destroyException' => 'manage_exceptions',
                        default => null,
                    },'AuthController' => in_array($action, ['me', 'logout', 'resendVerification'], true) ? '' : null,default => null
                };
            }
            abort_if($ability === null, 403);
            if ($ability !== '') {
                $tokens->authorize($user, $ability);
            }
            if ($controller === 'VersionController' && $action === 'approve' && $request->boolean('override')) {
                $tokens->authorize($user, 'override_release_readiness');
            }
        }

        return $next($request);
    }
}
