<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateComponentVersionRequest extends FormRequest
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
            'notes' => ['nullable', 'string', 'max:10000'],
            'ted_acceptance_status' => ['sometimes', 'string', Rule::in(['unknown', 'accepted', 'not_accepted'])],
            'ted_checked_at' => ['nullable', 'date', 'required_if:ted_acceptance_status,accepted,not_accepted'],
        ];
    }

    public function messages(): array
    {
        return ['notes.max' => 'Notes must be at most 10,000 characters.'];
    }
}
