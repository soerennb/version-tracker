<?php

namespace App\Http\Requests;

use App\Models\Version;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ApproveVersionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $version = $this->route('version');

        return $version instanceof Version && ($this->user()?->can('approve', $version) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'override' => ['sometimes', 'boolean'],
            'override_reason' => ['required_if:override,true', 'nullable', 'string', 'max:1000'],
        ];
    }
}
