<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComponentVersionResource extends JsonResource
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
            'tracked_component_id' => $this->tracked_component_id,
            'component' => $this->whenLoaded('component', fn (): array => [
                'name' => $this->component->name,
                'kind' => $this->component->kind,
                'customer_id' => $this->component->customer_id,
            ]),
            'version_label' => $this->version_label,
            'notes' => $this->notes,
            'ted' => $this->component?->kind === 'eforms_sdk' ? $this->tedAcceptance() : null,
        ];
    }
}
