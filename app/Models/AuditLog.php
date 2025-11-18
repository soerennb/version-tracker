<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    /** @use HasFactory<\Database\Factories\AuditLogFactory> */
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
