<?php

namespace App\Mcp\Tools;

use App\Services\ApiTokenService;
use App\Services\AttachmentUploadService;
use App\Services\RuntimeSettings;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;

class PrepareAttachmentUploadTool extends Tool
{
    protected string $name = 'prepare_attachment_upload';

    protected string $description = 'Prepare a staged attachment upload. Upload multipart file via HTTP between prepare and complete. The same bearer token is required throughout.';

    public function shouldRegister(Request $request): bool
    {
        $user = $request->user();
        $tokens = app(ApiTokenService::class);

        return $user && $tokens->allows($user, 'access_mcp') && ($tokens->allows($user, 'upload_files') || $tokens->allows($user, 'edit_files'));
    }

    public function schema(JsonSchema $schema): array
    {
        return ['version_id' => $schema->integer()->required(), 'filename' => $schema->string()->required(), 'attachment_id' => $schema->integer(), 'metadata' => $schema->object()->description('Optional attachment metadata: artifact_type, platform, architecture, checksum, checksum_algorithm, signature, verification_status, is_public.')];
    }

    public function handle(Request $request, AttachmentUploadService $service): Response|ResponseFactory
    {
        try {
            $upload = $service->prepare($request->user(), $request->all());
            $settings = app(RuntimeSettings::class)->security();

            return Response::structured(['upload_id' => $upload->id, 'url' => route('mcp.uploads.transfer', ['upload' => $upload]), 'method' => 'POST', 'field' => 'file', 'authorization' => 'Use the same Authorization: Bearer token as the MCP request.', 'expires_at' => $upload->expires_at->toISOString(), 'max_kb' => $settings->upload_max_kb, 'allowed_extensions' => $settings->upload_allowed_extensions]);
        } catch (ValidationException $exception) {
            return Response::error(json_encode(['code' => 'validation_error', 'fields' => $exception->errors()], JSON_THROW_ON_ERROR));
        } catch (AuthorizationException $exception) {
            return Response::error(json_encode(['code' => 'forbidden', 'message' => $exception->getMessage()], JSON_THROW_ON_ERROR));
        } catch (ModelNotFoundException) {
            return Response::error('Record not found.');
        } catch (\Throwable $exception) {
            report($exception);

            return Response::error(json_encode(['code' => 'internal_error', 'message' => 'The operation failed. Please try again or contact an administrator.'], JSON_THROW_ON_ERROR));
        }
    }
}
