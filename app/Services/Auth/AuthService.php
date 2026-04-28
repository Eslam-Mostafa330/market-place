<?php

namespace App\Services\Auth;

use App\Enums\DefineStatus;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\HttpException;
use App\Enums\TokenAbility;
use App\Enums\UserRole;
use App\Http\Resources\Auth\AuthUserResource;
use App\Models\PersonalAccessToken;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class AuthService
{
    /**
     * Validates the user's credentials, role, email verification, and account status.
     * Returns the authenticated user on success without issuing tokens
     */
    public function attemptLogin(array $credentials, ?UserRole $expectedRole = null): ?User
    {
        if (! Auth::attempt($credentials)) return null;

        $user = Auth::user();

        if ($expectedRole && $user->role !== $expectedRole) {
            return null;
        }

        if (! $user->email_verified_at) {
            throw new HttpException(403, __('auth.email_not_verified'));
        }

        if ($user->status === DefineStatus::INACTIVE) {
            throw new HttpException(403, __('auth.account_inactive'));
        }

        return $user;
    }

    /**
     * Issue tokens directly for a verified user (used after OTP verification).
     */
    public function issueTokens($user): array
    {
        $sessionId = Str::uuid()->toString();

        return [
            'access_token'  => $this->createAccessToken($user, $sessionId),
            'refresh_token' => $this->createRefreshToken($user, $sessionId),
            'user'          => new AuthUserResource($user),
        ];
    }

    /**
     * Logout current user.
     */
    public function logout($user): void
    {
        $currentToken = $user->currentAccessToken();

        if ($currentToken) {
            $sessionId = $currentToken->session_id;
            $currentToken->delete();
            $user->tokens()->where('name', 'RefreshToken')->where('session_id', $sessionId)->delete();
        }
    }

    /**
     * Logout all other devices when password changes.
     */
    public function logoutOtherDevicesOnPasswordChange(Authenticatable $user, array &$data, Request $request): void
    {
        if (! empty($data['password'])) {
            $user->tokens()
                ->where('id', '!=', $request->user()->currentAccessToken()->id)
                ->delete();
        }
    }

    /**
     * Refresh the access token for the current user.
     */
    public function refresh($user): array
    {
        $currentToken = $user->currentAccessToken();
        $sessionId    = $currentToken?->session_id;
        $currentToken?->delete();

        return [
            'access_token'  => $this->createAccessToken($user, $sessionId),
            'refresh_token' => $this->createRefreshToken($user, $sessionId),
        ];
    }

    /**
     * Create an access token for the given user and associate it with a session ID.
     */
    public function createAccessToken($user, string $sessionId): string
    {
        $token = PersonalAccessToken::createWithSession(
            $user,
            'AccessToken',
            [TokenAbility::ACCESS_API->value],
            Carbon::now()->addMinutes(config('sanctum.access_token_expiration')),
            $sessionId
        );

        return $token->plainTextToken;
    }

    /**
     * Create a refresh token for the given user and associate it with a session ID.
     */
    public function createRefreshToken($user, string $sessionId): string
    {
        $token = PersonalAccessToken::createWithSession(
            $user,
            'RefreshToken',
            [TokenAbility::ISSUE_ACCESS_TOKEN->value],
            Carbon::now()->addDays(config('sanctum.refresh_token_expiration')),
            $sessionId
        );

        return $token->plainTextToken;
    }

    /**
     * Revoke all tokens for a user (used for admin actions).
     */
    public function revokeAllTokens(User $user): void
    {
        $user->tokens()->delete();
    }
}