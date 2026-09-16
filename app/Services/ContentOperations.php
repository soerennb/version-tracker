<?php

namespace App\Services;

use App\Helpers\AuditHelper;
use App\Models\Software;
use App\Models\SoftwareDependency;
use App\Models\Version;
use App\Models\Vulnerability;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Validation\ValidationException;

class ContentOperations
{
    public function __construct(protected SoftwareService $software, protected VersionService $versions) {}

    /** @param class-string<Model> $modelClass @param array<string,mixed> $data */
    public function create(string $modelClass, array $data): Model
    {
        try {
            $model = match ($modelClass) {
                Software::class => $this->software->create($data), Version::class => $this->versions->create($data), default => $modelClass::query()->create($data)
            };
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['record' => 'A record with this unique combination already exists.']);
        }
        $this->audit($model, 'created', [], $model->toArray());

        return $model;
    }

    /** @param array<string,mixed> $data */
    public function update(Model $model, array $data): Model
    {
        $before = $model->toArray();
        try {
            $model = match (true) {
                $model instanceof Software => $this->software->update($model, $data), $model instanceof Version => $this->versions->update($model, $data), default => $this->updateModel($model, $data)
            };
        } catch (UniqueConstraintViolationException) {
            throw ValidationException::withMessages(['record' => 'A record with this unique combination already exists.']);
        }
        $this->audit($model, 'updated', $before, $model->toArray());

        return $model;
    }

    public function delete(Model $model): void
    {
        $before = $model->toArray();
        $model->delete();
        $this->audit($model, 'deleted', $before, []);
    }

    /** @param array<string,mixed> $data */
    protected function updateModel(Model $model, array $data): Model
    {
        $model->fill($data)->save();

        return $model->refresh();
    }

    /** @param array<string,mixed> $before @param array<string,mixed> $after */
    protected function audit(Model $model, string $action, array $before, array $after): void
    {
        if ($model instanceof SoftwareDependency || $model instanceof Vulnerability) {
            AuditHelper::logAction(auth()->user(), strtolower(class_basename($model)).'.'.$action, $model::class, (int) $model->id, $before, $after);
        }
    }
}
