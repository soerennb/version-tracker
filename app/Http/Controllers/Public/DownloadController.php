<?php

namespace App\Http\Controllers\Public;

use App\Enums\VersionStatus;
use App\Http\Controllers\Controller;
use App\Models\FileAttachment;
use App\Models\Version;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DownloadController extends Controller
{
    public function __invoke(Version $version, FileAttachment $fileAttachment): StreamedResponse
    {
        abort_unless(
            $version->status === VersionStatus::PUBLISHED
            && $fileAttachment->version_id === $version->id,
            404,
        );

        $path = $fileAttachment->file_path;
        abort_if($this->isUnsafePath($path), 404);

        $disk = Storage::disk(config('filesystems.default', 'local'));
        abort_unless($disk->exists($path), 404);

        return $disk->download($path, $fileAttachment->filename, [
            'Content-Type' => $fileAttachment->mime_type,
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function isUnsafePath(string $path): bool
    {
        return $path === ''
            || str_starts_with($path, '/')
            || str_contains($path, "\0")
            || preg_match('#(^|/)\.\.(/|$)#', str_replace('\\', '/', $path)) === 1;
    }
}
