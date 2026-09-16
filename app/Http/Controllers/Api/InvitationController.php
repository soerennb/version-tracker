<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\AcceptInvitationRequest;
use App\Http\Requests\StoreInvitationRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Models\UserInvitation;
use App\Notifications\UserInvitationNotification;
use App\Services\RuntimeSettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InvitationController extends Controller
{
    public function __construct(private readonly RuntimeSettings $runtimeSettings) {}

    public function store(StoreInvitationRequest $request): JsonResponse
    {
        abort_unless($this->runtimeSettings->invitationRegistrationAllowed(), 403);

        $data = $request->validated();
        $email = Str::lower($data['email']);
        $token = Str::random(64);

        UserInvitation::query()
            ->where('email', $email)
            ->whereNull('accepted_at')
            ->update(['expires_at' => now()]);

        $invitation = UserInvitation::query()->create([
            'email' => $email,
            'name' => $data['name'] ?? null,
            'token_hash' => hash('sha256', $token),
            'invited_by' => $request->user()->id,
            'expires_at' => now()->addDays($this->runtimeSettings->access()->invitation_expiry_days),
        ]);

        Notification::route('mail', $invitation->email)
            ->notify(new UserInvitationNotification($invitation, $token));

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

        $invitation = $this->usableInvitation($token);

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

        $invitation = $this->usableInvitation($token);

        if (User::query()->where('email', $invitation->email)->exists()) {
            throw ValidationException::withMessages([
                'email' => __('auth.invitation.email_exists'),
            ]);
        }

        $data = $request->validated();
        $user = DB::transaction(function () use ($data, $invitation): User {
            $user = User::query()->create([
                'name' => $data['name'] ?? $invitation->name ?? Str::before($invitation->email, '@'),
                'email' => $invitation->email,
                'password' => $data['password'],
                'role' => UserRole::VIEWER,
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();

            $invitation->forceFill(['accepted_at' => now()])->save();

            return $user;
        });

        Auth::guard('web')->login($user);

        if ($request->hasSession()) {
            $request->session()->regenerate();
        }

        return UserResource::make($user)
            ->additional(['email_verification_required' => false])
            ->response()
            ->setStatusCode(201);
    }

    protected function usableInvitation(string $token): UserInvitation
    {
        $invitation = UserInvitation::query()
            ->where('token_hash', hash('sha256', $token))
            ->first();

        abort_unless($invitation?->isUsable(), 404);

        return $invitation;
    }
}
