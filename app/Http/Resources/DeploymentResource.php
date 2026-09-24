<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DeploymentResource extends JsonResource
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
            'software' => $this->whenLoaded('software', fn (): array => [
                'id' => $this->software?->id,
                'name' => $this->software?->name,
            ]),
            'version_id' => $this->version_id,
            'version' => $this->whenLoaded('version', fn (): array => [
                'id' => $this->version?->id,
                'version_number' => $this->version?->version_number,
                'status' => $this->version?->status?->value,
                'approval_status' => $this->version?->approval_status?->value,
            ]),
            'environment_id' => $this->environment_id,
            'environment' => $this->whenLoaded('environment', fn (): array => [
                'id' => $this->environment?->id,
                'name' => $this->environment?->name,
                'code' => $this->environment?->code,
                'is_production' => $this->environment?->is_production,
            ]),
            'status' => $this->status?->value,
            'status_label' => $this->status?->label(),
            'scheduled_at' => $this->scheduled_at?->toISOString(),
            'approved_at' => $this->approved_at?->toISOString(),
            'started_at' => $this->started_at?->toISOString(),
            'completed_at' => $this->completed_at?->toISOString(),
            'created_by' => $this->created_by,
            'created_by_name' => $this->creator?->name,
            'approved_by' => $this->approved_by,
            'approved_by_name' => $this->approver?->name,
            'executed_by' => $this->executed_by,
            'executed_by_name' => $this->executor?->name,
            'change_reference' => $this->change_reference,
            'maintenance_window_start' => $this->maintenance_window_start?->toISOString(),
            'maintenance_window_end' => $this->maintenance_window_end?->toISOString(),
            'external_reference' => $this->external_reference,
            'source' => $this->source,
            'notes' => $this->notes,
            'result' => $this->result,
            'relation_type' => $this->relation_type,
            'related_deployment_id' => $this->related_deployment_id,
            'customization_version' => $this->customizationVersion ? ComponentVersionResource::make($this->customizationVersion->loadMissing('component')) : null,
            'release_composition' => $this->version?->composition ? ReleaseCompositionResource::make($this->version->composition) : null,
            'events' => DeploymentEventResource::collection($this->whenLoaded('events')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
