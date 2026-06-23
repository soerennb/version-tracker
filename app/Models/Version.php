<?php

namespace App\Models;

use App\Enums\ApprovalStatus;
use App\Enums\SupportStatus;
use App\Enums\VersionStatus;
use Database\Factories\VersionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Version extends Model
{
    /** @use HasFactory<VersionFactory> */
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'software_id',
        'created_by',
        'version_number',
        'release_date',
        'status',
        'approval_status',
        'rejection_reason',
        'eol_date',
        'lts_date',
        'support_status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'release_date' => 'date',
            'eol_date' => 'date',
            'lts_date' => 'date',
            'status' => VersionStatus::class,
            'approval_status' => ApprovalStatus::class,
            'support_status' => SupportStatus::class,
        ];
    }

    public function software(): BelongsTo
    {
        return $this->belongsTo(Software::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function textContents(): HasMany
    {
        return $this->hasMany(TextContent::class);
    }

    public function fileAttachments(): HasMany
    {
        return $this->hasMany(FileAttachment::class);
    }

    public function dependenciesAsMin(): HasMany
    {
        return $this->hasMany(SoftwareDependency::class, 'min_version_id');
    }

    public function dependenciesAsMax(): HasMany
    {
        return $this->hasMany(SoftwareDependency::class, 'max_version_id');
    }

    public function vulnerabilities(): HasMany
    {
        return $this->hasMany(Vulnerability::class, 'affected_version_id');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(VersionReview::class);
    }
}
