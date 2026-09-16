<?php

namespace App\Http\Requests;

use App\Models\Version;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReleaseExceptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $version = $this->route('version');

        return $version instanceof Version && ($this->user()?->can('manageExceptions', $version) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'check_code' => ['required', 'string', Rule::in([
                'blocking_vulnerabilities',
                'missing_sbom',
                'stale_sbom',
                'invalid_dependencies',
                'missing_attachments',
                'missing_lifecycle',
                'missing_required_content',
            ])],
            'reason' => ['required', 'string', 'max:2000'],
            'expires_at' => ['required', 'date', 'after:now'],
        ];
    }
}
