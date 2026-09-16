<?php

namespace App\Http\Requests;

use App\Enums\Language;
use App\Enums\VulnerabilitySeverity;
use App\Enums\VulnerabilityStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PublicSecurityRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'software' => ['nullable', 'integer', 'exists:software,id'],
            'severity' => ['nullable', Rule::enum(VulnerabilitySeverity::class)],
            'status' => ['nullable', Rule::in([
                VulnerabilityStatus::OPEN->value,
                VulnerabilityStatus::FIXED->value,
                VulnerabilityStatus::ACCEPTED->value,
            ])],
            'locale' => ['nullable', Rule::in(Language::values())],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ];
    }
}
