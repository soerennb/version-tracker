<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReleaseExceptionResource extends JsonResource
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
            'check_code' => $this->check_code,
            'reason' => $this->reason,
            'expires_at' => $this->expires_at?->toISOString(),
            'approved_at' => $this->approved_at?->toISOString(),
            'revoked_at' => $this->revoked_at?->toISOString(),
            'owner_id' => $this->owner_id,
            'owner_name' => $this->owner?->name,
            'approved_by' => $this->approved_by,
            'active' => $this->isActive(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
