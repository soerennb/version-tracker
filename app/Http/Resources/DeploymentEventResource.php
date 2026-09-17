<?php

namespace App\Http\Resources;

use App\Enums\DeploymentStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeploymentEventResource extends JsonResource
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
            'deployment_id' => $this->deployment_id,
            'actor_id' => $this->actor_id,
            'actor_name' => $this->actor?->name,
            'type' => $this->type?->value,
            'type_label' => $this->type?->label(),
            'from_status' => $this->from_status,
            'from_status_label' => DeploymentStatus::tryFrom((string) $this->from_status)?->label(),
            'to_status' => $this->to_status,
            'to_status_label' => DeploymentStatus::tryFrom((string) $this->to_status)?->label(),
            'comment' => $this->comment,
            'metadata' => $this->metadata,
            'interface' => $this->interface,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
