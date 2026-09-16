<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VersionSourceResource extends JsonResource
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
            'software_id' => $this->software_id,
            'version_id' => $this->version_id,
            'provider' => $this->provider,
            'source_kind' => $this->source_kind?->value,
            'external_id' => $this->external_id,
            'tag_name' => $this->tag_name,
            'name' => $this->name,
            'body' => $this->body,
            'source_url' => $this->source_url,
            'source_updated_at' => $this->source_updated_at?->toISOString(),
            'payload_hash' => $this->payload_hash,
            'imported_content_hash' => $this->imported_content_hash,
            'is_prerelease' => $this->is_prerelease,
            'last_seen_at' => $this->last_seen_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
