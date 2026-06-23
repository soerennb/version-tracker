<?php

namespace App\Http\Requests;

use App\Enums\SupportStatus;
use App\Helpers\VersionHelper;
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
            'version_number' => ['required', 'string', 'max:50', 'regex:'.VersionHelper::semverRegex()],
            'release_date' => ['required', 'date'],
            'eol_date' => ['nullable', 'date', 'after_or_equal:release_date'],
            'lts_date' => ['nullable', 'date', 'after_or_equal:release_date'],
            'support_status' => ['nullable', Rule::enum(SupportStatus::class)],
        ];
    }
}
