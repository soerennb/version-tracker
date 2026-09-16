<?php

namespace App\Models;

use App\Enums\SourceSyncStatus;
use Database\Factories\SourceSyncRunFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SourceSyncRun extends Model
{
    /** @use HasFactory<SourceSyncRunFactory> */
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'software_id',
        'provider',
        'status',
        'started_at',
        'finished_at',
        'created_count',
        'updated_count',
        'unchanged_count',
        'skipped_count',
        'error_count',
        'error_message',
        'errors',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SourceSyncStatus::class,
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
            'created_count' => 'integer',
            'updated_count' => 'integer',
            'unchanged_count' => 'integer',
            'skipped_count' => 'integer',
            'error_count' => 'integer',
            'errors' => 'array',
        ];
    }

    public function software(): BelongsTo
    {
        return $this->belongsTo(Software::class);
    }
}
