<?php

namespace App\Http\Controllers\Api;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\ForgotPasswordRequest;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\ResendVerificationRequest;
use App\Http\Requests\ResetPasswordRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\RuntimeSettings;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private readonly RuntimeSettings $runtimeSettings) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        if (! $this->runtimeSettings->registrationAllowed()) {
            throw new AuthorizationException(__('auth.registration_unavailable'));
        }

        $data = $request->validated();
        $user = User::query()->create([
            'name' => $data['name'],
            'email' => Str::lower($data['email']),
            'password' => $data['password'],
            'role' => UserRole::VIEWER,
            'is_active' => true,
        ]);

        if (! $this->runtimeSettings->access()->email_verification_required) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        Auth::guard('web')->login($user);
        $this->regenerateSession($request);

        if ($this->runtimeSettings->access()->email_verification_required) {
            $user->sendEmailVerificationNotification();
        }

        return UserResource::make($user)
            ->additional(['email_verification_required' => $this->runtimeSettings->access()->email_verification_required])
            ->response()
            ->setStatusCode(201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $data = $request->validated();

        if (! Auth::guard('web')->attempt([
            'email' => Str::lower($data['email']),
            'password' => $data['password'],
            'is_active' => true,
        ], (bool) ($data['remember'] ?? false))) {
            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        $this->regenerateSession($request);
        $user = $request->user()->load('subscriptions.software');

        return UserResource::make($user)
            ->additional(['email_verification_required' => $this->runtimeSettings->access()->email_verification_required && ! $user->hasVerifiedEmail()])
            ->response();
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();
        Auth::guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return response()->json(status: 204);
    }

    public function me(Request $request): JsonResponse
    {
        return UserResource::make($request->user()->load('subscriptions.software'))
            ->response();
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        Password::sendResetLink($request->validated());

        return response()->json([
            'message' => __('auth.passwords.sent'),
        ], 202);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->validated(),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return response()->json([
            'message' => __('auth.passwords.reset'),
        ]);
    }

    public function verify(EmailVerificationRequest $request): JsonResponse|RedirectResponse
    {
        $request->fulfill();

        if ($request->expectsJson()) {
            return UserResource::make($request->user()->fresh())->response();
        }

        return redirect('/account/verify?verified=1');
    }

    public function resendVerification(ResendVerificationRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($this->runtimeSettings->access()->email_verification_required && ! $user->hasVerifiedEmail()) {
            $user->sendEmailVerificationNotification();
        }

        return response()->json([
            'message' => __('auth.verification.sent'),
        ], 202);
    }

    private function regenerateSession(Request $request): void
    {
        if ($request->hasSession()) {
            $request->session()->regenerate();
        }
    }
}
