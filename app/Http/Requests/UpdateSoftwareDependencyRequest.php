<?php

namespace App\Http\Requests;

use App\Models\SoftwareDependency;
use App\Rules\AcyclicSoftwareDependency;
use App\Rules\VersionBelongsToSoftware;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSoftwareDependencyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage_dependencies') ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var SoftwareDependency|null $softwareDependency */
        $softwareDependency = $this->route('software_dependency') ?? $this->route('softwareDependency');
        $softwareId = $softwareDependency?->software_id;
        $dependsOnSoftwareId = $this->integer('depends_on_software_id')
            ?: $softwareDependency?->depends_on_software_id;

        return [
            'depends_on_software_id' => ['sometimes', 'integer', 'exists:software,id', new AcyclicSoftwareDependency($softwareId, $softwareDependency?->id)],
            'applies_to_version_id' => ['nullable', 'integer', 'exists:versions,id', new VersionBelongsToSoftware($softwareId)],
            'min_version_id' => ['nullable', 'integer', 'exists:versions,id', new VersionBelongsToSoftware($dependsOnSoftwareId)],
            'max_version_id' => ['nullable', 'integer', 'exists:versions,id', new VersionBelongsToSoftware($dependsOnSoftwareId)],
            'dependency_type' => ['sometimes', 'string', 'max:255'],
        ];
    }
}
