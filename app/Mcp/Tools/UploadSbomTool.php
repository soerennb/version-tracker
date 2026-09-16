<?php

namespace App\Mcp\Tools;

use App\Http\Resources\SbomDocumentResource;
use App\Models\Version;
use App\Services\ApiTokenService;
use App\Services\SbomIngestionService;
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

class UploadSbomTool extends Tool
{
    protected string $name = 'upload_sbom';

    protected string $description = 'Upload and parse a CycloneDX or SPDX JSON SBOM for a draft version.';

    public function shouldRegister(Request $request): bool
    {
        $user = $request->user();

        return $user !== null
            && app(ApiTokenService::class)->allows($user, 'access_mcp')
            && app(ApiTokenService::class)->allows($user, 'upload_sboms')
            && $user->can('upload_sboms');
    }

    /**
     * @return array<string, Type>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'version_id' => $schema->integer()->min(1)->required(),
            'document' => $schema->string()->description('Complete SBOM JSON document.')->required(),
            'format' => $schema->string()->description('cyclonedx or spdx; omit to detect automatically.'),
            'source' => $schema->string()->description('manual, ci, or api.'),
            'idempotency_key' => $schema->string()->description('Stable key for safe retries.'),
        ];
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        try {
            $user = $request->user();
            if (! $user) {
                throw new AuthorizationException;
            }

            app(ApiTokenService::class)->authorize($user, 'access_mcp');
            app(ApiTokenService::class)->authorize($user, 'upload_sboms');
            $data = $request->validate([
                'version_id' => ['required', 'integer', 'min:1'],
                'document' => ['required', 'string'],
                'format' => ['sometimes', 'nullable', 'string', 'in:cyclonedx,spdx'],
                'source' => ['sometimes', 'nullable', 'string', 'in:manual,ci,api'],
                'idempotency_key' => ['sometimes', 'nullable', 'string', 'max:191'],
            ]);
            $version = Version::query()->findOrFail($data['version_id']);
            Gate::authorize('uploadSbom', $version);

            $result = app(SbomIngestionService::class)->ingest(
                version: $version,
                payload: $data['document'],
                format: $data['format'] ?? null,
                source: $data['source'] ?? 'api',
                idempotencyKey: $data['idempotency_key'] ?? null,
                user: $user,
            );

            return Response::structured([
                'data' => (new SbomDocumentResource($result['document']))->resolve(),
                'created' => $result['created'],
            ]);
        } catch (ValidationException $exception) {
            return Response::error(json_encode(['code' => 'validation_error', 'fields' => $exception->errors()], JSON_THROW_ON_ERROR));
        } catch (AuthorizationException $exception) {
            return Response::error(json_encode(['code' => 'forbidden', 'message' => $exception->getMessage()], JSON_THROW_ON_ERROR));
        } catch (ModelNotFoundException) {
            return Response::error(json_encode(['code' => 'not_found', 'message' => 'The requested version does not exist.'], JSON_THROW_ON_ERROR));
        } catch (\Throwable $exception) {
            report($exception);

            return Response::error(json_encode(['code' => 'internal_error', 'message' => 'The SBOM could not be uploaded.'], JSON_THROW_ON_ERROR));
        }
    }
}
