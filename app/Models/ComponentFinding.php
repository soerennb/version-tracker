<?php

namespace App\Models;

use App\Enums\ExploitabilityStatus;
use App\Enums\VulnerabilitySeverity;
use App\Enums\VulnerabilityStatus;
use Database\Factories\ComponentFindingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComponentFinding extends Model
{
    /** @use HasFactory<ComponentFindingFactory> */
    use HasFactory;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'sbom_document_id',
        'sbom_component_id',
        'external_id',
        'source',
        'severity',
        'cvss_score',
        'epss_score',
        'is_kev',
        'risk_score',
        'risk_factors',
        'exploitability',
        'status',
        'description',
        'affected_range',
        'fixed_version',
        'source_url',
        'details',
        'first_seen_at',
        'last_seen_at',
        'resolved_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'severity' => VulnerabilitySeverity::class,
            'cvss_score' => 'decimal:1',
            'epss_score' => 'decimal:4',
            'is_kev' => 'boolean',
            'risk_score' => 'decimal:2',
            'risk_factors' => 'array',
            'exploitability' => ExploitabilityStatus::class,
            'status' => VulnerabilityStatus::class,
            'details' => 'array',
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'resolved_at' => 'datetime',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(SbomDocument::class, 'sbom_document_id');
    }

    public function component(): BelongsTo
    {
        return $this->belongsTo(SbomComponent::class, 'sbom_component_id');
    }
}
