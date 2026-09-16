<?php

namespace App\Http\Requests;

use App\Services\RuntimeSettings;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreFileAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('upload_files') ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $settings = app(RuntimeSettings::class)->security();
        $allowedExtensions = implode(',', $settings->upload_allowed_extensions);
        $maxKilobytes = $settings->upload_max_kb;

        return [
            'file' => ['required', 'file', 'max:'.$maxKilobytes, 'mimes:'.$allowedExtensions],
            'artifact_type' => ['sometimes', 'nullable', 'string', 'max:100'],
            'platform' => ['sometimes', 'nullable', 'string', 'max:100'],
            'architecture' => ['sometimes', 'nullable', 'string', 'max:100'],
            'checksum' => ['sometimes', 'nullable', 'string', 'max:255'],
            'checksum_algorithm' => ['sometimes', 'nullable', 'string', 'max:50'],
            'signature' => ['sometimes', 'nullable', 'string'],
            'verification_status' => ['sometimes', 'string', 'in:unverified,pending,verified,failed'],
            'is_public' => ['sometimes', 'boolean'],
        ];
    }
}
