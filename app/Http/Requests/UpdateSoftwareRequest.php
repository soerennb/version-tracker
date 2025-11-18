<?php

namespace App\Http\Requests;

use App\Enums\SoftwareStatus;
use App\Models\Software;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSoftwareRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var Software $software */
        $software = $this->route('software');

        return $software
            ? ($this->user()?->can('update', $software) ?? false)
            : false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Software $software */
        $software = $this->route('software');

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('software', 'name')->ignore($software?->id),
            ],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::enum(SoftwareStatus::class)],
            'current_version' => ['nullable', 'string', 'max:50'],
            'last_release_date' => ['nullable', 'date'],
            'license_type' => ['nullable', 'string', 'max:255'],
            'compliance_status' => ['required', 'string', 'max:255'],
            'github_repo_url' => ['nullable', 'url', 'max:255'],
        ];
    }
}
