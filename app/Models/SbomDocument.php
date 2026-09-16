<?php

namespace App\Models;

use Database\Factories\SbomDocumentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SbomDocument extends Model
{
    /** @use HasFactory<SbomDocumentFactory> */
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'version_id',
        'uploaded_by',
        'filename',
        'format',
        'spec_version',
        'serial_number',
        'document_hash',
        'idempotency_key',
        'source',
        'status',
        'component_count',
        'finding_count',
        'parsed_at',
        'last_enriched_at',
        'enrichment_status',
        'enrichment_error',
        'error_message',
        'payload',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'component_count' => 'integer',
            'finding_count' => 'integer',
            'parsed_at' => 'datetime',
            'last_enriched_at' => 'datetime',
        ];
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(Version::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function components(): HasMany
    {
        return $this->hasMany(SbomComponent::class);
    }

    public function findings(): HasMany
    {
        return $this->hasMany(ComponentFinding::class);
    }
}
