<?php

namespace App\Models;

use Database\Factories\FileAttachmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FileAttachment extends Model
{
    /** @use HasFactory<FileAttachmentFactory> */
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'version_id',
        'filename',
        'artifact_type',
        'platform',
        'architecture',
        'file_path',
        'mime_type',
        'size',
        'checksum',
        'checksum_algorithm',
        'signature',
        'verification_status',
        'is_public',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size' => 'integer',
            'is_public' => 'boolean',
        ];
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(Version::class);
    }
}
