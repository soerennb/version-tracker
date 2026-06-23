<?php

namespace App\Models;

use App\Enums\RejectReason;
use App\Enums\ReviewAction;
use Database\Factories\VersionReviewFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VersionReview extends Model
{
    /** @use HasFactory<VersionReviewFactory> */
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'version_id',
        'user_id',
        'action',
        'reject_reason',
        'comment',
        'metadata',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'action' => ReviewAction::class,
            'reject_reason' => RejectReason::class,
            'metadata' => 'array',
        ];
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(Version::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
