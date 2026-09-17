<?php

namespace App\Models;

use App\Enums\DeploymentEventType;
use Database\Factories\DeploymentEventFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

class DeploymentEvent extends Model
{
    /** @use HasFactory<DeploymentEventFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::updating(function (): never {
            throw new LogicException('Deployment events are append-only.');
        });

        static::deleting(function (): never {
            throw new LogicException('Deployment events are append-only.');
        });
    }

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'deployment_id',
        'actor_id',
        'type',
        'from_status',
        'to_status',
        'comment',
        'metadata',
        'interface',
        'api_token_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => DeploymentEventType::class,
            'metadata' => 'array',
        ];
    }

    public function deployment(): BelongsTo
    {
        return $this->belongsTo(Deployment::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
