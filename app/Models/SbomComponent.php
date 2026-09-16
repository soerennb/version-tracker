<?php

namespace App\Models;

use Database\Factories\SbomComponentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SbomComponent extends Model
{
    /** @use HasFactory<SbomComponentFactory> */
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'sbom_document_id',
        'bom_ref',
        'package_type',
        'group_name',
        'name',
        'version',
        'purl',
        'cpe',
        'supplier',
        'licenses',
        'hashes',
        'properties',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'licenses' => 'array',
            'hashes' => 'array',
            'properties' => 'array',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(SbomDocument::class, 'sbom_document_id');
    }

    public function findings(): HasMany
    {
        return $this->hasMany(ComponentFinding::class);
    }
}
