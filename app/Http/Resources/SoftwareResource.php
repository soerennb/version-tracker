<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SoftwareResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'current_version' => $this->current_version,
            'last_release_date' => $this->last_release_date?->toDateString(),
            'license_type' => $this->license_type,
            'compliance_status' => $this->compliance_status?->value,
            'compliance_status_label' => $this->compliance_status?->label(),
            'github_repo_url' => $this->github_repo_url,
            'versions_count' => $this->when(isset($this->versions_count), $this->versions_count),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            'creator' => $this->whenLoaded('creator', fn () => [
                'id' => $this->creator?->id,
                'name' => $this->creator?->name,
            ]),
            'versions' => VersionResource::collection($this->whenLoaded('versions')),
            'dependencies_outgoing' => SoftwareDependencyResource::collection(
                $this->whenLoaded('dependenciesOutgoing')
            ),
            'dependencies_incoming' => SoftwareDependencyResource::collection(
                $this->whenLoaded('dependenciesIncoming')
            ),
        ];
    }
}
