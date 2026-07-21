<?php

namespace App\Services\Auth;

use App\Enums\DefineStatus;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;

class AuthService
{
    /**
     * Validates the user's credentials, role, email verification, and account status.
     * Returns the authenticated user on success without issuing tokens
     */
    public function attemptLogin(array $credentials, ?UserRole $expectedRole = null): ?User
    {
        if (! Auth::once($credentials)) return null;

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
     * Grant access to a verified user.
     *
     * Web clients start a session, while mobile clients receive access and refresh tokens.
     */
    public function grantAccess(User $user, Request $request): array
    {
        if ($this->isStatefulRequest($request)) {
            $this->startWebSession($user, $request);

            return ['user' => new AuthUserResource($user)];
        }

        return $this->issueTokens($user);
    }

    /**
     * Issue tokens directly for a verified user (used by mobile clients).
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
     * Logout the current user.
     *
     * Web sessions are invalidated and their cookie is flushed, while mobile
     * clients have the current session's access and refresh tokens revoked.
     */
    public function logout(Request $request): void
    {
        if ($this->isStatefulRequest($request)) {
            $this->endWebSession($request);

            return;
        }

        $this->revokeCurrentTokens($request->user());
    }

    /**
     * Logout other devices after a password change.
     *
     * Web sessions are invalidated, while mobile clients revoke all other tokens.
     */
    public function logoutOtherDevicesOnPasswordChange(Authenticatable $user, array &$data, Request $request): void
    {
        if (empty($data['password'])) {
            return;
        }

        if ($this->isStatefulRequest($request)) {
            Auth::logoutOtherDevices($data['password']);
            return;
        }

        $user->tokens()
            ->where('id', '!=', $request->user()->currentAccessToken()->id)
            ->delete();
    }

    /**
     * Refresh tokens for mobile clients.
     * Web clients use session authentication and cannot refresh tokens.
     */
    public function refresh($user, Request $request): array
    {
        if ($this->isStatefulRequest($request)) {
            throw new HttpException(403, __('auth.token_refresh_not_allowed'));
        }

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
     * Revoke all user access by removing tokens and web sessions.
     */
    public function revokeAllAccess(User $user): void
    {
        $this->revokeAllTokens($user);
        $this->revokeWebSessions($user);
    }

    /**
     * Log the user into the stateful "web" guard, rotating the session id to
     * prevent session fixation. This sets the httpOnly session cookie on the response.
     */
    private function startWebSession(User $user, Request $request): void
    {
        Auth::guard('web')->login($user);
        $request->session()->regenerate();
    }

    /**
     * Invalidate the web session and issue a fresh CSRF token.
     */
    private function endWebSession(Request $request): void
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    /**
     * Revoke the access token and its paired refresh token for the current session.
     */
    private function revokeCurrentTokens($user): void
    {
        $currentToken = $user->currentAccessToken();

        if ($currentToken) {
            $sessionId = $currentToken->session_id;
            $currentToken->delete();
            $user->tokens()->where('name', 'RefreshToken')->where('session_id', $sessionId)->delete();
        }
    }

    /**
     * Revoke all tokens for a user (used for admin actions).
     */
    public function revokeAllTokens(User $user): void
    {
        $user->tokens()->delete();
    }

    /**
     * Delete the user's server-side web sessions,
     * immediately invalidating any active browser sessions.
     */
    private function revokeWebSessions(User $user): void
    {
        if (config('session.driver') !== 'database') {
            return;
        }

        DB::table(config('session.table', 'sessions'))
            ->where('user_id', $user->id)
            ->delete();
    }

    /**
     * Check if the request comes from the first-party SPA.
     */
    private function isStatefulRequest(Request $request): bool
    {
        return EnsureFrontendRequestsAreStateful::fromFrontend($request);
    }
}