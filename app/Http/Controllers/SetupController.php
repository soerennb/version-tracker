<?php

namespace App\Http\Controllers;

use App\Http\Requests\SetupRequest;
use App\Services\SetupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SetupController extends Controller
{
    public function __construct(private readonly SetupService $setupService) {}

    public function create(): View
    {
        abort_unless($this->setupService->isAvailable(), 404);

        return view('setup.index');
    }

    public function store(SetupRequest $request): RedirectResponse
    {
        abort_unless($this->setupService->hasValidToken((string) $request->string('token')), 403);

        $user = $this->setupService->complete($request->validated());

        return redirect('/admin/login')->with('status', __('setup.completed', ['email' => $user->email]));
    }
}
