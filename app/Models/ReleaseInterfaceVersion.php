<?php

namespace App\Models;

use Database\Factories\ReleaseInterfaceVersionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReleaseInterfaceVersion extends Model
{
    /** @use HasFactory<ReleaseInterfaceVersionFactory> */
    use HasFactory;

    protected $fillable = ['release_composition_id', 'component_version_id'];

    public function composition(): BelongsTo
    {
        return $this->belongsTo(ReleaseComposition::class, 'release_composition_id');
    }

    public function componentVersion(): BelongsTo
    {
        return $this->belongsTo(ComponentVersion::class);
    }
}
