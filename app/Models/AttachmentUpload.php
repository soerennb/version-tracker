<?php

namespace App\Models;

use Database\Factories\AttachmentUploadFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Sanctum\PersonalAccessToken;

class AttachmentUpload extends Model
{
    /** @use HasFactory<AttachmentUploadFactory> */
    use HasFactory, HasUuids;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'expires_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(Version::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function token(): BelongsTo
    {
        return $this->belongsTo(PersonalAccessToken::class, 'personal_access_token_id');
    }

    public function resultAttachment(): BelongsTo
    {
        return $this->belongsTo(FileAttachment::class, 'result_attachment_id');
    }
}
