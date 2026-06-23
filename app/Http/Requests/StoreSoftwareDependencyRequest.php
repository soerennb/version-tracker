<?php

namespace App\Http\Requests;

use App\Rules\AcyclicSoftwareDependency;
use App\Rules\VersionBelongsToSoftware;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreSoftwareDependencyRequest extends FormRequest
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
        $softwareId = $this->integer('software_id') ?: null;
        $dependsOnSoftwareId = $this->integer('depends_on_software_id') ?: null;

        return [
            'software_id' => ['required', 'integer', 'exists:software,id'],
            'depends_on_software_id' => ['required', 'different:software_id', 'integer', 'exists:software,id', new AcyclicSoftwareDependency($softwareId)],
            'applies_to_version_id' => ['nullable', 'integer', 'exists:versions,id', new VersionBelongsToSoftware($softwareId)],
            'min_version_id' => ['nullable', 'integer', 'exists:versions,id', new VersionBelongsToSoftware($dependsOnSoftwareId)],
            'max_version_id' => ['nullable', 'integer', 'exists:versions,id', new VersionBelongsToSoftware($dependsOnSoftwareId)],
            'dependency_type' => ['required', 'string', 'max:255'],
        ];
    }
}
