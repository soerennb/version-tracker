<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComponentFindingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sbom_document_id' => $this->sbom_document_id,
            'sbom_component_id' => $this->sbom_component_id,
            'external_id' => $this->external_id,
            'source' => $this->source,
            'severity' => $this->severity?->value,
            'severity_label' => $this->severity?->label(),
            'cvss_score' => $this->cvss_score,
            'epss_score' => $this->epss_score,
            'is_kev' => $this->is_kev,
            'risk_score' => $this->risk_score,
            'risk_factors' => $this->risk_factors ?? [],
            'exploitability' => $this->exploitability?->value,
            'status' => $this->status?->value,
            'description' => $this->description,
            'affected_range' => $this->affected_range,
            'fixed_version' => $this->fixed_version,
            'source_url' => $this->source_url,
            'details' => $this->details ?? [],
            'first_seen_at' => $this->first_seen_at?->toISOString(),
            'last_seen_at' => $this->last_seen_at?->toISOString(),
            'resolved_at' => $this->resolved_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
