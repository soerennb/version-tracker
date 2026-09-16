<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicAdvisoryResource extends JsonResource
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
            'cve_id' => $this->cve_id,
            'software' => [
                'id' => $this->affectedVersion?->software?->id,
                'name' => $this->affectedVersion?->software?->name,
            ],
            'version' => [
                'id' => $this->affectedVersion?->id,
                'number' => $this->affectedVersion?->version_number,
            ],
            'severity' => $this->severity?->value,
            'severity_label' => $this->severity?->label(),
            'cvss_score' => $this->cvss_score,
            'description' => $this->description,
            'source' => $this->source,
            'source_url' => $this->source_url,
            'affected_range' => $this->affected_range,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'exploitability' => $this->exploitability?->value,
            'exploitability_label' => $this->exploitability?->label(),
            'published_date' => $this->published_date?->toDateString(),
            'source_updated_at' => $this->source_updated_at?->toISOString(),
            'fixed_version' => $this->fixedVersion ? [
                'id' => $this->fixedVersion->id,
                'number' => $this->fixedVersion->version_number,
            ] : null,
            'links' => [
                'product' => $this->affectedVersion?->software ? '/products/'.$this->affectedVersion->software->id : null,
                'release' => $this->affectedVersion ? '/releases/'.$this->affectedVersion->id : null,
                'detail' => '/security/'.$this->id,
            ],
        ];
    }
}
