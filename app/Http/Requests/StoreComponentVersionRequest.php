<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreComponentVersionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('edit_versions') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tracked_component_id' => ['required', 'integer', 'exists:tracked_components,id'],
            'version_label' => ['required', 'string', 'max:100', Rule::unique('component_versions', 'version_label')->where('tracked_component_id', $this->input('tracked_component_id'))],
            'notes' => ['nullable', 'string', 'max:10000'],
            'ted_acceptance_status' => ['sometimes', 'string', Rule::in(['unknown', 'accepted', 'not_accepted'])],
            'ted_checked_at' => ['nullable', 'date', 'required_if:ted_acceptance_status,accepted,not_accepted'],
        ];
    }

    public function messages(): array
    {
        return ['version_label.unique' => 'This version already exists for the component.'];
    }
}
