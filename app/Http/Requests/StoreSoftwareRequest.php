<?php

namespace App\Http\Requests;

use App\Enums\SoftwareStatus;
use App\Models\Software;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSoftwareRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('create', Software::class) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:software,name'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::enum(SoftwareStatus::class)],
            'license_type' => ['nullable', 'string', 'max:255'],
            'compliance_status' => ['required', 'string', 'max:255'],
            'github_repo_url' => ['nullable', 'url', 'max:255'],
        ];
    }
}
