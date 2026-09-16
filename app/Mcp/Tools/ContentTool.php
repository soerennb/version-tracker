<?php

namespace App\Mcp\Tools;

use App\Models\Version;
use App\Services\ApiTokenService;
use App\Services\ContentOperations;
use App\Services\ContentValidation;
use App\Services\FileAttachmentService;
use App\Services\VersionService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Tool;

abstract class ContentTool extends Tool
{
    protected string $entity;

    protected string $operation;

    public function ability(): string
    {
        $ability = config('mcp.entities.'.$this->entity.'.ability');
        if ($ability === 'files') {
            return match ($this->operation) {
                'list','show' => 'download_files','update' => 'edit_files','delete' => 'delete_files',default => 'upload_files'
            };
        }

        return match ($this->operation) {
            'list','show' => 'view_'.$ability,'update' => 'edit_'.$ability,'approve','reject' => 'approve_versions','publish' => 'publish_versions',default => $this->operation.'_'.$ability
        };
    }

    public function shouldRegister(Request $request): bool
    {
        $user = $request->user();

        $tokens = app(ApiTokenService::class);
        if (! $user || ! $tokens->allows($user, 'access_mcp') || ! $tokens->allows($user, $this->ability())) {
            return false;
        }
        $ability = $this->entity === 'software_dependencies' ? 'manage_dependencies' : ($this->operation === 'reject' ? 'edit_versions' : $this->ability());
        if (Gate::allows($ability)) {
            return true;
        }
        $canOwn = $this->operation === 'update' || ($this->operation === 'delete' && in_array($this->entity, ['text_contents', 'attachments'], true));
        if (! $canOwn || ! in_array($this->entity, ['software', 'versions', 'text_contents', 'attachments'], true)) {
            return false;
        }
        $modelClass = config('mcp.entities.'.$this->entity.'.model');
        $query = $modelClass::query();
        if ($this->entity === 'software') {
            return $query->where('created_by', $user->id)->exists();
        }
        if ($this->entity === 'versions') {
            $query->where('status', 'draft');
        }

        return $query->whereHas($this->entity === 'versions' ? 'software' : 'version.software', fn ($software) => $software->where('created_by', $user->id))->exists();
    }

    /** @return array<string,mixed> */
    public function schema(JsonSchema $schema): array
    {
        if ($this->operation === 'list') {
            return ['search' => $schema->string(), 'software_id' => $schema->integer(), 'version_id' => $schema->integer(), 'page' => $schema->integer()->min(1), 'per_page' => $schema->integer()->min(1)->max(100)];
        }
        $fields = [];
        foreach (config('mcp.entities.'.$this->entity.'.fields') as $name => $type) {
            $fields[$name] = $schema->$type()->nullable();
        }
        $arguments = $this->operation === 'create' ? [] : ['id' => $schema->integer()->required()];
        if (in_array($this->operation, ['create', 'update'], true)) {
            $arguments['data'] = $schema->object($fields)->required()->description('Content fields. Dates use YYYY-MM-DD; version_number uses semantic versioning.');
        }
        if ($this->entity === 'text_contents' && $this->operation === 'create') {
            $arguments['version_id'] = $schema->integer()->required();
        }
        if ($this->operation === 'approve') {
            $arguments['override'] = $schema->boolean();
            $arguments['override_reason'] = $schema->string();
        }
        if ($this->operation === 'reject') {
            $arguments['reason'] = $schema->string()->required();
            $arguments['reject_reason'] = $schema->string()->required();
        }

        return $arguments;
    }

    public function handle(Request $request): Response|ResponseFactory
    {
        try {
            return Response::structured($this->execute($request));
        } catch (ValidationException $exception) {
            return Response::error(json_encode(['code' => 'validation_error', 'fields' => $exception->errors()], JSON_THROW_ON_ERROR));
        } catch (AuthorizationException $exception) {
            return Response::error(json_encode(['code' => 'forbidden', 'message' => $exception->getMessage()], JSON_THROW_ON_ERROR));
        } catch (ModelNotFoundException) {
            return Response::error(json_encode(['code' => 'not_found', 'message' => 'The requested record does not exist.'], JSON_THROW_ON_ERROR));
        } catch (\Throwable $exception) {
            report($exception);

            return Response::error(json_encode(['code' => 'internal_error', 'message' => 'The operation failed. Please try again or contact an administrator.'], JSON_THROW_ON_ERROR));
        }
    }

    /** @return array<string,mixed> */
    protected function execute(Request $request): array
    {
        $user = $request->user();
        if (! $user) {
            throw new AuthorizationException;
        }
        app(ApiTokenService::class)->authorize($user, 'access_mcp');
        app(ApiTokenService::class)->authorize($user, $this->ability());
        $definition = config('mcp.entities.'.$this->entity);
        $modelClass = $definition['model'];
        if (! in_array($this->operation, ['list', 'create'], true)) {
            $request->validate(['id' => ['required', 'integer', 'min:1']]);
        }
        $model = in_array($this->operation, ['list', 'create'], true) ? null : $modelClass::query()->findOrFail($request->get('id'));
        if ($this->entity === 'software_dependencies') {
            Gate::authorize('manage_dependencies');
        } else {
            Gate::authorize(match ($this->operation) {
                'list' => 'viewAny','show' => 'view','delete' => 'delete',default => $this->operation
            }, $model ?? $modelClass);
        }
        if ($this->operation === 'list') {
            $request->validate(['page' => ['sometimes', 'integer', 'min:1'], 'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'], 'search' => ['sometimes', 'string', 'max:255'], 'software_id' => ['sometimes', 'integer'], 'version_id' => ['sometimes', 'integer']]);
            $query = $modelClass::query()->with($definition['relations']);
            if ($request->filled('search')) {
                $query->where($definition['search'], 'like', '%'.$request->get('search').'%');
            }
            if ($request->filled('software_id')) {
                if (in_array($this->entity, ['versions', 'software_dependencies'], true)) {
                    $query->where('software_id', $request->get('software_id'));
                } elseif ($this->entity === 'software') {
                    $query->whereKey($request->get('software_id'));
                } else {
                    $query->whereHas($this->entity === 'vulnerabilities' ? 'affectedVersion' : 'version', fn ($q) => $q->where('software_id', $request->get('software_id')));
                }
            }
            if ($request->filled('version_id') && $this->entity !== 'software') {
                $query->where(match ($this->entity) {
                    'versions' => 'id','vulnerabilities' => 'affected_version_id','software_dependencies' => 'applies_to_version_id',default => 'version_id'
                }, $request->get('version_id'));
            }
            $page = $query->orderBy('id')->paginate((int) $request->get('per_page', 25), ['*'], 'page', (int) $request->get('page', 1));

            return ['data' => $page->getCollection()->map(fn ($record) => $this->serialize($record))->all(), 'meta' => ['current_page' => $page->currentPage(), 'last_page' => $page->lastPage(), 'per_page' => $page->perPage(), 'total' => $page->total()]];
        }
        if ($this->operation === 'show') {
            return ['data' => $this->serialize($model->load($definition['relations']))];
        }
        if ($this->operation === 'delete') {
            if ($this->entity === 'attachments') {
                app(FileAttachmentService::class)->delete($model);
            } else {
                app(ContentOperations::class)->delete($model);
            }

            return ['deleted' => true, 'id' => $model->id];
        }
        $parameters = [$definition['parameter'] => $model];
        if ($this->entity === 'text_contents' && $this->operation === 'create') {
            $request->validate(['version_id' => ['required', 'integer', 'min:1']]);
            $version = Version::query()->findOrFail($request->get('version_id'));
            Gate::authorize('view', $version);
            $parameters['version'] = $version;
        }
        if (in_array($this->operation, ['approve', 'reject', 'publish'], true)) {
            $service = app(VersionService::class);
            if ($this->operation === 'publish') {
                return ['data' => $this->serialize($service->publish($model))];
            }
            $requestClass = 'App\\Http\\Requests\\'.ucfirst($this->operation).'VersionRequest';
            $data = app(ContentValidation::class)->validate($requestClass, $request->all(), ['version' => $model]);
            if ($this->operation === 'approve' && ($data['override'] ?? false)) {
                app(ApiTokenService::class)->authorize($user, 'override_release_readiness');
            }
            $result = $this->operation === 'approve' ? $service->approve($model, (bool) ($data['override'] ?? false), $data['override_reason'] ?? null) : $service->reject($model, $data['reason'], $data['reject_reason']);

            return ['data' => $this->serialize($result)];
        }
        $input = $request->validate(['data' => ['required', 'array']])['data'];
        $requestClass = 'App\\Http\\Requests\\'.($this->operation === 'create' ? 'Store' : 'Update').class_basename($modelClass).'Request';
        $data = app(ContentValidation::class)->validate($requestClass, $input, $parameters);
        if ($this->entity === 'text_contents' && $this->operation === 'create') {
            $data['version_id'] = $parameters['version']->id;
        }
        $operations = app(ContentOperations::class);
        $result = $this->operation === 'create' ? $operations->create($modelClass, $data) : ($this->entity === 'attachments' ? app(FileAttachmentService::class)->update($model, $data) : $operations->update($model, $data));

        return ['data' => $this->serialize($result->load($definition['relations']))];
    }

    /** @return array<string,mixed> */
    protected function serialize(Model $model): array
    {
        $resource = 'App\\Http\\Resources\\'.class_basename($model).'Resource';

        return (new $resource($model))->resolve(request());
    }
}
