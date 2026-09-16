<?php

namespace App\Mcp\Tools;

use App\Http\Resources\FileAttachmentResource;
use App\Services\ApiTokenService;
use App\Services\AttachmentUploadService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;

class CompleteAttachmentUploadTool extends Tool
{
    protected string $name = 'complete_attachment_upload';

    protected string $description = 'Complete a staged attachment upload. Upload multipart file via HTTP between prepare and complete. The same bearer token is required throughout.';

    public function shouldRegister(Request $request): bool
    {
        $user = $request->user();
        $tokens = app(ApiTokenService::class);

        return $user && $tokens->allows($user, 'access_mcp') && ($tokens->allows($user, 'upload_files') || $tokens->allows($user, 'edit_files'));
    }

    public function schema(JsonSchema $schema): array
    {
        return ['upload_id' => $schema->string()->required()];
    }

    public function handle(Request $request, AttachmentUploadService $service): Response|ResponseFactory
    {
        try {
            $data = $request->validate(['upload_id' => ['required', 'uuid']]);

            return Response::structured(['data' => (new FileAttachmentResource($service->complete($request->user(), $data['upload_id'])))->resolve(request())]);
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
