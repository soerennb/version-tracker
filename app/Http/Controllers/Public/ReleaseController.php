<?php

namespace App\Http\Controllers\Public;

use App\Enums\VersionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\PublicReleaseRequest;
use App\Http\Resources\PublicReleaseResource;
use App\Models\Version;

class ReleaseController extends Controller
{
    public function show(PublicReleaseRequest $request, Version $version): PublicReleaseResource
    {
        abort_unless($version->status === VersionStatus::PUBLISHED, 404);

        $version->load([
            'software:id,name,description',
            'textContents' => fn ($query) => $query->orderBy('language'),
            'fileAttachments' => fn ($query) => $query->where('is_public', true),
            'sources',
            'composition.baselineVersion.component',
            'composition.eformsComponentVersion.component',
            'composition.activeEformsSdkVersion.component',
            'composition.supportedInterfaces.componentVersion.component',
            'vulnerabilities' => fn ($query) => $query
                ->whereNot('status', 'false_positive')
                ->with('fixedVersion:id,version_number'),
        ]);

        return new PublicReleaseResource($version);
    }
}
