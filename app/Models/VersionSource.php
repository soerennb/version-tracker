<?php

namespace App\Models;

use App\Enums\VersionSourceKind;
use Database\Factories\VersionSourceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VersionSource extends Model
{
    /** @use HasFactory<VersionSourceFactory> */
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'software_id',
        'version_id',
        'provider',
        'source_kind',
        'external_id',
        'tag_name',
        'name',
        'body',
        'source_url',
        'source_updated_at',
        'payload_hash',
        'imported_content_hash',
        'is_prerelease',
        'last_seen_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'source_kind' => VersionSourceKind::class,
            'source_updated_at' => 'datetime',
            'is_prerelease' => 'boolean',
            'last_seen_at' => 'datetime',
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
}
