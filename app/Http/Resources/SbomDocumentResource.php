<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SbomDocumentResource extends JsonResource
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
            'version_id' => $this->version_id,
            'uploaded_by' => $this->uploaded_by,
            'filename' => $this->filename,
            'format' => $this->format,
            'spec_version' => $this->spec_version,
            'serial_number' => $this->serial_number,
            'document_hash' => $this->document_hash,
            'idempotency_key' => $this->idempotency_key,
            'source' => $this->source,
            'status' => $this->status,
            'component_count' => $this->component_count,
            'finding_count' => $this->finding_count,
            'parsed_at' => $this->parsed_at?->toISOString(),
            'last_enriched_at' => $this->last_enriched_at?->toISOString(),
            'enrichment_status' => $this->enrichment_status,
            'enrichment_error' => $this->enrichment_error,
            'error_message' => $this->error_message,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'components' => SbomComponentResource::collection($this->whenLoaded('components')),
            'findings' => ComponentFindingResource::collection($this->whenLoaded('findings')),
        ];
    }
}
