<?php

namespace App\Http\Requests;

use App\Models\SoftwareDependency;
use App\Rules\AcyclicSoftwareDependency;
use App\Rules\VersionBelongsToSoftware;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSoftwareDependencyRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'scope_key' => SoftwareDependency::makeScopeKey(
                $this->integer('software_id'),
                $this->integer('depends_on_software_id'),
                $this->exists('applies_to_version_id') && $this->input('applies_to_version_id') !== null
                    ? $this->integer('applies_to_version_id')
                    : null,
                (string) $this->input('dependency_type', 'runtime'),
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
        $softwareId = $this->integer('software_id') ?: null;
        $dependsOnSoftwareId = $this->integer('depends_on_software_id') ?: null;

        return [
            'software_id' => ['required', 'integer', 'exists:software,id'],
            'depends_on_software_id' => ['required', 'different:software_id', 'integer', 'exists:software,id', new AcyclicSoftwareDependency($softwareId)],
            'applies_to_version_id' => ['nullable', 'integer', 'exists:versions,id', new VersionBelongsToSoftware($softwareId)],
            'min_version_id' => ['nullable', 'integer', 'exists:versions,id', new VersionBelongsToSoftware($dependsOnSoftwareId)],
            'max_version_id' => ['nullable', 'integer', 'exists:versions,id', new VersionBelongsToSoftware($dependsOnSoftwareId)],
            'dependency_type' => ['required', 'string', 'max:255'],
            'scope_key' => [
                'required',
                'string',
                'size:64',
                Rule::unique('software_dependencies', 'scope_key'),
            ],
        ];
    }
}
