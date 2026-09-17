<?php

namespace App\Http\Requests;

use App\Models\Deployment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreDeploymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Deployment::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'software_id' => ['required', 'integer', 'exists:software,id'],
            'version_id' => ['required', 'integer', 'exists:versions,id'],
            'environment_id' => ['required', 'integer', 'exists:environments,id'],
            'scheduled_at' => ['nullable', 'date'],
            'change_reference' => ['nullable', 'string', 'max:150'],
            'maintenance_window_start' => ['nullable', 'date'],
            'maintenance_window_end' => ['nullable', 'date', 'after_or_equal:maintenance_window_start'],
            'external_reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:10000'],
        ];
    }
}
