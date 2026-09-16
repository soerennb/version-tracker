<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SbomComponentResource extends JsonResource
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
            'bom_ref' => $this->bom_ref,
            'package_type' => $this->package_type,
            'group_name' => $this->group_name,
            'name' => $this->name,
            'version' => $this->version,
            'purl' => $this->purl,
            'cpe' => $this->cpe,
            'supplier' => $this->supplier,
            'licenses' => $this->licenses ?? [],
            'hashes' => $this->hashes ?? [],
            'properties' => $this->properties ?? [],
            'findings' => ComponentFindingResource::collection($this->whenLoaded('findings')),
        ];
    }
}
