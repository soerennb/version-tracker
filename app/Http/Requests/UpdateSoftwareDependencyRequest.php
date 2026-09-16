<?php

namespace App\Http\Requests;

use App\Models\SoftwareDependency;
use App\Rules\AcyclicSoftwareDependency;
use App\Rules\VersionBelongsToSoftware;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSoftwareDependencyRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $dependency = $this->dependency();

        $this->merge([
            'scope_key' => SoftwareDependency::makeScopeKey(
                (int) ($dependency?->software_id ?? 0),
                $this->exists('depends_on_software_id')
                    ? $this->integer('depends_on_software_id')
                    : (int) ($dependency?->depends_on_software_id ?? 0),
                $this->exists('applies_to_version_id')
                    ? ($this->input('applies_to_version_id') === null ? null : $this->integer('applies_to_version_id'))
                    : $dependency?->applies_to_version_id,
                (string) ($this->input('dependency_type') ?? $dependency?->dependency_type ?? 'runtime'),
            ),
        ]);
    }

    public function authorize(): bool
    {
        return $this->user()?->can('manage_dependencies') ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $softwareDependency = $this->dependency();
        $softwareId = $softwareDependency?->software_id;
        $dependsOnSoftwareId = $this->integer('depends_on_software_id')
            ?: $softwareDependency?->depends_on_software_id;

        return [
            'depends_on_software_id' => ['sometimes', 'integer', 'exists:software,id', new AcyclicSoftwareDependency($softwareId, $softwareDependency?->id)],
            'applies_to_version_id' => ['nullable', 'integer', 'exists:versions,id', new VersionBelongsToSoftware($softwareId)],
            'min_version_id' => ['nullable', 'integer', 'exists:versions,id', new VersionBelongsToSoftware($dependsOnSoftwareId)],
            'max_version_id' => ['nullable', 'integer', 'exists:versions,id', new VersionBelongsToSoftware($dependsOnSoftwareId)],
            'dependency_type' => ['sometimes', 'string', 'max:255'],
            'scope_key' => [
                'required',
                'string',
                'size:64',
                Rule::unique('software_dependencies', 'scope_key')->ignore($softwareDependency?->id),
            ],
        ];
    }

    protected function dependency(): ?SoftwareDependency
    {
        $dependency = $this->route('software_dependency') ?? $this->route('softwareDependency');

        return $dependency instanceof SoftwareDependency ? $dependency : null;
    }
}
