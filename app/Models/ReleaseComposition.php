<?php

namespace App\Models;

use Database\Factories\ReleaseCompositionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReleaseComposition extends Model
{
    /** @use HasFactory<ReleaseCompositionFactory> */
    use HasFactory;

    protected $fillable = ['version_id', 'baseline_version_id', 'eforms_component_version_id', 'active_eforms_sdk_version_id'];

    public function version(): BelongsTo
    {
        return $this->belongsTo(Version::class);
    }

    public function baselineVersion(): BelongsTo
    {
        return $this->belongsTo(ComponentVersion::class, 'baseline_version_id');
    }

    public function eformsComponentVersion(): BelongsTo
    {
        return $this->belongsTo(ComponentVersion::class, 'eforms_component_version_id');
    }

    public function activeEformsSdkVersion(): BelongsTo
    {
        return $this->belongsTo(ComponentVersion::class, 'active_eforms_sdk_version_id');
    }

    public function supportedInterfaces(): HasMany
    {
        return $this->hasMany(ReleaseInterfaceVersion::class);
    }
}
