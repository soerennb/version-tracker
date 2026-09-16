<?php

namespace App\Models;

use Database\Factories\SoftwareDependencyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SoftwareDependency extends Model
{
    /** @use HasFactory<SoftwareDependencyFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (SoftwareDependency $dependency): void {
            if ($dependency->software_id === null
                || $dependency->depends_on_software_id === null
                || $dependency->dependency_type === null) {
                return;
            }

            $dependency->scope_key = self::makeScopeKey(
                (int) $dependency->software_id,
                (int) $dependency->depends_on_software_id,
                $dependency->applies_to_version_id === null ? null : (int) $dependency->applies_to_version_id,
                (string) $dependency->dependency_type,
            );
        });
    }

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'software_id',
        'depends_on_software_id',
        'applies_to_version_id',
        'min_version_id',
        'max_version_id',
        'dependency_type',
    ];

    public static function makeScopeKey(
        int $softwareId,
        int $dependsOnSoftwareId,
        ?int $appliesToVersionId,
        string $dependencyType,
    ): string {
        return hash('sha256', (string) json_encode([
            'software_id' => $softwareId,
            'depends_on_software_id' => $dependsOnSoftwareId,
            'applies_to_version_id' => $appliesToVersionId,
            'dependency_type' => strtolower(trim($dependencyType)),
        ], JSON_THROW_ON_ERROR));
    }

    public function software(): BelongsTo
    {
        return $this->belongsTo(Software::class);
    }

    public function dependsOnSoftware(): BelongsTo
    {
        return $this->belongsTo(Software::class, 'depends_on_software_id');
    }

    public function appliesToVersion(): BelongsTo
    {
        return $this->belongsTo(Version::class, 'applies_to_version_id');
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
