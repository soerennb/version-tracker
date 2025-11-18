<?php

namespace App\Http\Requests;

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
        return [
            'depends_on_software_id' => ['sometimes', 'integer', 'exists:software,id'],
            'min_version_id' => ['nullable', 'integer', 'exists:versions,id'],
            'max_version_id' => ['nullable', 'integer', 'exists:versions,id'],
            'dependency_type' => ['sometimes', 'string', 'max:255'],
        ];
    }
}
