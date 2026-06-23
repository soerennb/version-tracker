<?php

namespace App\Http\Controllers;

use App\Enums\VersionStatus;
use App\Enums\VulnerabilityStatus;
use App\Models\Vulnerability;
use Illuminate\Contracts\View\View;

class PublicSecurityController extends Controller
{
    public function index(): View
    {
        return view('public.security', [
            'advisories' => Vulnerability::query()
                ->with(['affectedVersion.software', 'fixedVersion'])
                ->whereHas('affectedVersion', fn ($query) => $query->where('status', VersionStatus::PUBLISHED->value))
                ->where('status', '!=', VulnerabilityStatus::FALSE_POSITIVE->value)
                ->latest('published_date')
                ->paginate(20),
        ]);
    }
}
