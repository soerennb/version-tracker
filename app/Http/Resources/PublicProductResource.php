<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $latestVersion = $this->versions->first();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'status' => $this->status?->value,
            'current_release' => $latestVersion ? [
                'id' => $latestVersion->id,
                'version' => $latestVersion->version_number,
                'release_date' => $latestVersion->release_date?->toDateString(),
                'support_status' => $latestVersion->support_status?->value,
                'eol_date' => $latestVersion->eol_date?->toDateString(),
                'open_vulnerabilities' => $latestVersion->open_vulnerabilities_count,
            ] : null,
        ];
    }
}
