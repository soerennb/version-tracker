<?php

namespace App\Mcp\Tools;

use App\Models\Version;
use App\Services\ApiTokenService;
use App\Services\ReleaseReadinessService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\JsonSchema\Types\Type;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;

class ShowVersionReadinessTool extends Tool
{
    protected string $name = 'show_version_readiness';

    protected string $description = 'Evaluate release readiness, SBOM freshness, security risks and active exceptions.';

    public function shouldRegister(Request $request): bool
    {
        $user = $request->user();

        return $user !== null
            && app(ApiTokenService::class)->allows($user, 'access_mcp')
            && app(ApiTokenService::class)->allows($user, 'view_readiness')
            && $user->can('view_readiness');
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return ['version_id' => $schema->integer()->min(1)->required()];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        try {
            $user = $request->user();
            if (! $user) {
                throw new AuthorizationException;
            }

            app(ApiTokenService::class)->authorize($user, 'access_mcp');
            app(ApiTokenService::class)->authorize($user, 'view_readiness');
            $data = $request->validate(['version_id' => ['required', 'integer', 'min:1']]);
            $version = Version::query()->findOrFail($data['version_id']);
            Gate::authorize('viewReadiness', $version);

            return Response::structured([
                'version_id' => $version->id,
                'readiness' => app(ReleaseReadinessService::class)->evaluate($version),
            ]);
        } catch (ValidationException $exception) {
            return Response::error(json_encode(['code' => 'validation_error', 'fields' => $exception->errors()], JSON_THROW_ON_ERROR));
        } catch (AuthorizationException $exception) {
            return Response::error(json_encode(['code' => 'forbidden', 'message' => $exception->getMessage()], JSON_THROW_ON_ERROR));
        } catch (ModelNotFoundException) {
            return Response::error(json_encode(['code' => 'not_found', 'message' => 'The requested version does not exist.'], JSON_THROW_ON_ERROR));
        } catch (\Throwable $exception) {
            report($exception);

            return Response::error(json_encode(['code' => 'internal_error', 'message' => 'Readiness evaluation failed.'], JSON_THROW_ON_ERROR));
        }
    }
}
