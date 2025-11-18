<?php

namespace App\Http\Requests;

use App\Enums\ApprovalStatus;
use App\Enums\VersionStatus;
use App\Models\Version;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVersionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var Version $version */
        $version = $this->route('version');

        return $version
            ? ($this->user()?->can('update', $version) ?? false)
            : false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'version_number' => ['required', 'string', 'max:50', 'regex:/^\\d+(\\.\\d+){0,2}$/'],
            'release_date' => ['required', 'date'],
            'status' => ['required', Rule::enum(VersionStatus::class)],
            'approval_status' => ['required', Rule::enum(ApprovalStatus::class)],
            'eol_date' => ['nullable', 'date', 'after_or_equal:release_date'],
            'lts_date' => ['nullable', 'date', 'after_or_equal:release_date'],
            'support_status' => ['nullable', 'string', 'max:255'],
        ];
    }
}
