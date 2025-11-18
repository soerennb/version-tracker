<?php

namespace App\Http\Requests;

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
        return [
            'software_id' => ['required', 'integer', 'exists:software,id'],
            'depends_on_software_id' => ['required', 'different:software_id', 'integer', 'exists:software,id'],
            'min_version_id' => ['nullable', 'integer', 'exists:versions,id'],
            'max_version_id' => ['nullable', 'integer', 'exists:versions,id'],
            'dependency_type' => ['required', 'string', 'max:255'],
        ];
    }
}
