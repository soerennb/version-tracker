<?php

namespace App\Http\Requests;

use App\Models\Version;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreReleaseCompositionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $version = $this->route('version');

        return $version instanceof Version
            && $version->status?->isDraft()
            && ($this->user()?->can('update', $version) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'baseline_version_id' => ['required', 'integer', 'exists:component_versions,id'],
            'eforms_component_version_id' => ['required', 'integer', 'exists:component_versions,id'],
            'active_eforms_sdk_version_id' => ['required', 'integer', 'exists:component_versions,id'],
            'supported_interface_version_ids' => ['required', 'array', 'min:1'],
            'supported_interface_version_ids.*' => ['required', 'integer', 'distinct', 'exists:component_versions,id'],
        ];
    }

    public function messages(): array
    {
        return ['supported_interface_version_ids.min' => 'Select at least the active eForms SDK version.'];
    }
}
