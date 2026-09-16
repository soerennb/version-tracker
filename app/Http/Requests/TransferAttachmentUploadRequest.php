<?php

namespace App\Http\Requests;

use App\Services\RuntimeSettings;
use Illuminate\Foundation\Http\FormRequest;

class TransferAttachmentUploadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $settings = app(RuntimeSettings::class)->security();

        return ['file' => ['required', 'file', 'max:'.$settings->upload_max_kb, 'mimes:'.implode(',', $settings->upload_allowed_extensions)]];
    }

    public function messages(): array
    {
        return ['file.required' => __('api_tokens.file_required')];
    }
}
