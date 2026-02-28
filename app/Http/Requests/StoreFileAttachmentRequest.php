<?php

namespace App\Http\Requests;

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
        $allowedExtensions = implode(',', config('security.upload_allowed_extensions', ['pdf']));
        $maxKilobytes = (int) config('security.upload_max_kb', 10240);

        return [
            'file' => ['required', 'file', 'max:'.$maxKilobytes, 'mimes:'.$allowedExtensions],
        ];
    }
}
