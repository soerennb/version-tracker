<?php

namespace App\Models;

use Database\Factories\AuditLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AuditLog extends Model
{
    /** @use HasFactory<AuditLogFactory> */
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'action',
        'model_type',
        'model_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'old_values' => 'array',
            'new_values' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getModelInstance(): ?Model
    {
        if (! class_exists($this->model_type)) {
            return null;
        }

        /** @var class-string<Model> $modelClass */
        $modelClass = $this->model_type;

        return $modelClass::find($this->model_id);
    }

    public function hasAuditChanges(): bool
    {
        return ! empty($this->old_values) || ! empty($this->new_values);
    }

    public function getModelLabelAttribute(): string
    {
        $model = class_basename($this->model_type);
        $translation = __("filament.audit.models.{$model}");

        return $translation === "filament.audit.models.{$model}" ? Str::headline($model) : $translation;
    }

    public function getActionLabelAttribute(): string
    {
        $translation = __("filament.audit.actions.{$this->action}");

        return $translation === "filament.audit.actions.{$this->action}"
            ? Str::headline(str_replace('.', ' ', $this->action))
            : $translation;
    }

    public function getSubjectLabelAttribute(): string
    {
        $model = $this->getModelInstance();

        return match (true) {
            $model instanceof Software => $model->name,
            $model instanceof Version => ($model->software?->name ? $model->software->name.' · ' : '').$model->version_number,
            $model instanceof Vulnerability => $model->cve_id,
            $model instanceof TextContent => $model->title,
            $model instanceof VersionReview => ($model->version?->version_number ? 'v'.$model->version->version_number : $this->model_label.' #'.$this->model_id),
            default => $this->model_label.' #'.$this->model_id,
        };
    }

    /**
     * @return array<string, array{old:mixed,new:mixed}>
     */
    public function getChangedFields(): array
    {
        $changed = [];

        foreach ($this->old_values ?? [] as $field => $oldValue) {
            $newValue = $this->new_values[$field] ?? null;

            if ($oldValue !== $newValue) {
                $changed[$field] = [
                    'old' => $oldValue,
                    'new' => $newValue,
                ];
            }
        }

        foreach ($this->new_values ?? [] as $field => $newValue) {
            if (! isset($this->old_values[$field])) {
                $changed[$field] = [
                    'old' => null,
                    'new' => $newValue,
                ];
            }
        }

        return $changed;
    }
}
