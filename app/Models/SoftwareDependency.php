<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SoftwareDependency extends Model
{
    /** @use HasFactory<\Database\Factories\SoftwareDependencyFactory> */
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'software_id',
        'depends_on_software_id',
        'min_version_id',
        'max_version_id',
        'dependency_type',
    ];

    public function software(): BelongsTo
    {
        return $this->belongsTo(Software::class);
    }

    public function dependsOnSoftware(): BelongsTo
    {
        return $this->belongsTo(Software::class, 'depends_on_software_id');
    }

    public function minVersion(): BelongsTo
    {
        return $this->belongsTo(Version::class, 'min_version_id');
    }

    public function maxVersion(): BelongsTo
    {
        return $this->belongsTo(Version::class, 'max_version_id');
    }

    public function hasVersionConstraint(): bool
    {
        return filled($this->min_version_id) || filled($this->max_version_id);
    }
}
