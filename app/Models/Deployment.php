<?php

namespace App\Models;

use App\Enums\DeploymentStatus;
use Database\Factories\DeploymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Deployment extends Model
{
    /** @use HasFactory<DeploymentFactory> */
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'software_id',
        'version_id',
        'environment_id',
        'status',
        'scheduled_at',
        'approved_at',
        'started_at',
        'completed_at',
        'created_by',
        'approved_by',
        'executed_by',
        'change_reference',
        'maintenance_window_start',
        'maintenance_window_end',
        'external_reference',
        'source',
        'notes',
        'result',
        'relation_type',
        'related_deployment_id',
        'customization_version_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => DeploymentStatus::class,
            'scheduled_at' => 'datetime',
            'approved_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'maintenance_window_start' => 'datetime',
            'maintenance_window_end' => 'datetime',
        ];
    }

    public function software(): BelongsTo
    {
        return $this->belongsTo(Software::class);
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(Version::class);
    }

    public function customizationVersion(): BelongsTo
    {
        return $this->belongsTo(ComponentVersion::class, 'customization_version_id');
    }

    public function environment(): BelongsTo
    {
        return $this->belongsTo(Environment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function executor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executed_by');
    }

    public function relatedDeployment(): BelongsTo
    {
        return $this->belongsTo(self::class, 'related_deployment_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(DeploymentEvent::class)->latest('created_at');
    }

    public function isTerminal(): bool
    {
        return $this->status?->isTerminal() ?? false;
    }

    public function isEditable(): bool
    {
        return $this->status === DeploymentStatus::PLANNED;
    }
}
