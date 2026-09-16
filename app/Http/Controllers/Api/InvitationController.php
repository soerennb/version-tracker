<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AcceptInvitationRequest;
use App\Http\Requests\StoreInvitationRequest;
use App\Http\Resources\UserResource;
use App\Services\InvitationService;
use App\Services\RuntimeSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class InvitationController extends Controller
{
    public function __construct(
        private readonly InvitationService $invitationService,
        private readonly RuntimeSettings $runtimeSettings,
    ) {}

    public function store(StoreInvitationRequest $request): JsonResponse
    {
        abort_unless($this->runtimeSettings->invitationRegistrationAllowed(), 403);

        $invitation = $this->invitationService->create($request->user(), $request->validated());

        return response()->json([
            'data' => [
                'id' => $invitation->id,
                'email' => $invitation->email,
                'name' => $invitation->name,
                'expires_at' => $invitation->expires_at?->toISOString(),
            ],
        ], 201);
    }

    public function show(string $token): JsonResponse
    {
        abort_unless($this->runtimeSettings->invitationRegistrationAllowed(), 404);

        $invitation = $this->invitationService->findUsable($token);
        abort_unless($invitation, 404);

        return response()->json([
            'data' => [
                'email' => $invitation->email,
                'name' => $invitation->name,
                'expires_at' => $invitation->expires_at?->toISOString(),
            ],
        ]);
    }

    public function accept(AcceptInvitationRequest $request, string $token): JsonResponse
    {
        abort_unless($this->runtimeSettings->invitationRegistrationAllowed(), 404);

        $user = $this->invitationService->accept($token, $request->validated());

        Auth::guard('web')->login($user);

        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        return UserResource::make($user)
            ->additional(['email_verification_required' => false])
            ->response()
            ->setStatusCode(201);
    }
}
