<?php

namespace App\Http\Resources;

use App\Models\ComponentVersion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReleaseCompositionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing([
            'baselineVersion.component',
            'eformsComponentVersion.component',
            'activeEformsSdkVersion.component',
            'supportedInterfaces.componentVersion.component',
        ]);

        return [
            'baseline' => $this->componentVersion($this->baselineVersion),
            'eforms_component' => $this->componentVersion($this->eformsComponentVersion),
            'active_eforms_sdk' => $this->componentVersion($this->activeEformsSdkVersion),
            'supported_interfaces' => $this->supportedInterfaces->map(fn ($item): array => $this->componentVersion($item->componentVersion))->values(),
            'ted' => $this->activeEformsSdkVersion->tedAcceptance(),
        ];
    }

    /** @return array{id:int,name:string,kind:string,version:string} */
    private function componentVersion(ComponentVersion $version): array
    {
        return [
            'id' => $version->id,
            'name' => $version->component->name,
            'kind' => $version->component->kind,
            'version' => $version->version_label,
        ];
    }
}
