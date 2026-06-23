<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SoftwareDependencyResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'software_id' => $this->software_id,
            'depends_on_software_id' => $this->depends_on_software_id,
            'software' => $this->whenLoaded('software', fn () => [
                'id' => $this->software?->id,
                'name' => $this->software?->name,
            ]),
            'depends_on' => $this->whenLoaded('dependsOnSoftware', fn () => [
                'id' => $this->dependsOnSoftware?->id,
                'name' => $this->dependsOnSoftware?->name,
            ]),
            'applies_to_version' => $this->whenLoaded('appliesToVersion', fn () => [
                'id' => $this->appliesToVersion?->id,
                'version_number' => $this->appliesToVersion?->version_number,
            ]),
            'min_version' => $this->whenLoaded('minVersion', fn () => [
                'id' => $this->minVersion?->id,
                'version_number' => $this->minVersion?->version_number,
            ]),
            'max_version' => $this->whenLoaded('maxVersion', fn () => [
                'id' => $this->maxVersion?->id,
                'version_number' => $this->maxVersion?->version_number,
            ]),
            'dependency_type' => $this->dependency_type,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
