<?php

namespace App\Http\Requests;

use App\Models\FileAttachment;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateFileAttachmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var FileAttachment|null $fileAttachment */
        $fileAttachment = $this->route('file_attachment') ?? $this->route('fileAttachment');

        return $fileAttachment
            ? ($this->user()?->can('update', $fileAttachment) ?? false)
            : false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $allowedExtensions = implode(',', config('security.upload_allowed_extensions', ['pdf']));
        $maxKilobytes = (int) config('security.upload_max_kb', 10240);

        return [
            'file' => ['nullable', 'file', 'max:'.$maxKilobytes, 'mimes:'.$allowedExtensions],
        ];
    }
}
