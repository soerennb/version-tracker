<?php

namespace App\Models;

use App\Enums\SoftwareStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Software extends Model
{
    /** @use HasFactory<\Database\Factories\SoftwareFactory> */
    use HasFactory;

    use SoftDeletes;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'description',
        'status',
        'current_version',
        'last_release_date',
        'created_by',
        'updated_by',
        'license_type',
        'compliance_status',
        'github_repo_url',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => SoftwareStatus::class,
            'last_release_date' => 'date',
            'deleted_at' => 'datetime',
        ];
    }

    public function versions(): HasMany
    {
        return $this->hasMany(Version::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function dependenciesOutgoing(): HasMany
    {
        return $this->hasMany(SoftwareDependency::class, 'software_id');
    }

    public function dependenciesIncoming(): HasMany
    {
        return $this->hasMany(SoftwareDependency::class, 'depends_on_software_id');
    }

    public function latestVersion(): HasOne
    {
        return $this->hasOne(Version::class)->latestOfMany('release_date');
    }

    public function getVersionsCountAttribute(): int
    {
        if (array_key_exists('versions_count', $this->attributes)) {
            return (int) $this->attributes['versions_count'];
        }

        return $this->versions()->count();
    }
}
