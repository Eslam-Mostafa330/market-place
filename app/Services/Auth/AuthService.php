<?php

namespace App\Services\Auth;

use App\Enums\DefineStatus;
use App\Enums\TokenAbility;
use App\Enums\UserRole;
use App\Http\Resources\Auth\AuthUserResource;
use App\Models\PersonalAccessToken;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful;
use Laravel\Sanctum\TransientToken;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AuthService
{
    /**
     * Validates the user's credentials, role, email verification, and account status.
     * Returns the authenticated user on success without issuing tokens
     */
    public function attemptLogin(array $credentials, ?UserRole $expectedRole = null): ?User
    {
        $guard = Auth::guard('web');

        if (! $guard->once($credentials)) return null;

        $user = $guard->user();

        $guard->forgetUser();

        if ($expectedRole && $user->role !== $expectedRole) {
            return null;
        }

        if (! $user->email_verified_at) {
            throw new HttpException(403, __('auth.email_not_verified'));
        }

        $this->ensureActive($user);

        return $user;
    }

    /**
     * Grant access to a verified user.
     *
     * The SPA gets an httpOnly session cookie, everyone else gets a token pair, Status is re-checked
     */
    public function grantAccess(User $user, Request $request): array
    {
        $this->ensureActive($user);

        if (EnsureFrontendRequestsAreStateful::fromFrontend($request)) {
            Auth::guard('web')->login($user);
            $request->session()->regenerate();

            return ['user' => new AuthUserResource($user)];
        }

        $tokens = $this->tokenPair($user, Str::uuid()->toString());
        $tokens['user'] = new AuthUserResource($user);

        return $tokens;
    }

    /**
     * Logout the current user, the web session is invalidated and its cookie
     * flushed, or the current session's token pair is revoked.
     */
    public function logout(Request $request): void
    {
        if ($this->onWebSession($request)) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return;
        }

        $user = $request->user();

        $user->tokens()->where('session_id', $user->currentAccessToken()->session_id)->delete();
    }

    /**
     * Logout other devices after a password change, keeping the current one.
     */
    public function logoutOtherDevicesOnPasswordChange(User $user, array $data, Request $request): void
    {
        if (empty($data['password'])) {
            return;
        }

        if ($this->onWebSession($request)) {
            $this->webSessionsOf($user)?->where('id', '!=', $request->session()->getId())->delete();

            return;
        }

        $user->tokens()->where('session_id', '!=', $user->currentAccessToken()->session_id)->delete();
    }

    /**
     * Rotate a mobile client's token pair.
     */
    public function refresh(User $user, Request $request): array
    {
        if ($this->onWebSession($request)) {
            throw new HttpException(403, __('auth.token_refresh_not_allowed'));
        }

        $sessionId = $user->currentAccessToken()->session_id;

        $user->tokens()->where('session_id', $sessionId)->delete();

        return $this->tokenPair($user, $sessionId);
    }

    /**
     * Revoke all of a user's access, tokens and web sessions alike.
     */
    public function revokeAllAccess(User $user): void
    {
        $user->tokens()->delete();
        $this->webSessionsOf($user)?->delete();
    }

    /**
     * Mint an access and refresh token sharing one session id, which is what
     * lets logout and rotation act on the pair as a unit.
     */
    private function tokenPair(User $user, string $sessionId): array
    {
        return [
            'access_token' => $this->createToken(
                $user,
                'AccessToken',
                TokenAbility::ACCESS_API,
                Carbon::now()->addMinutes(config('sanctum.access_token_expiration')),
                $sessionId,
            ),
            'refresh_token' => $this->createToken(
                $user,
                'RefreshToken',
                TokenAbility::ISSUE_ACCESS_TOKEN,
                Carbon::now()->addDays(config('sanctum.refresh_token_expiration')),
                $sessionId,
            ),
        ];
    }

    /**
     * Create a single token and return its plain text value.
     */
    private function createToken(User $user, string $name, TokenAbility $ability, Carbon $expiresAt, string $sessionId): string
    {
        return PersonalAccessToken::createWithSession(
            $user,
            $name,
            [$ability->value],
            $expiresAt,
            $sessionId,
        )->plainTextToken;
    }

    /**
     * Reject a login or an access grant for a deactivated account.
     */
    private function ensureActive(User $user): void
    {
        if ($user->status === DefineStatus::INACTIVE) {
            throw new HttpException(403, __('auth.account_inactive'));
        }
    }

    /**
     * Check how the current request authenticated rather than where it came from
     */
    private function onWebSession(Request $request): bool
    {
        return $request->user()?->currentAccessToken() instanceof TransientToken;
    }

    /**
     * Query the user's session rows, or null when sessions aren't database-backed.
     */
    private function webSessionsOf(User $user): ?Builder
    {
        if (config('session.driver') !== 'database') {
            return null;
        }

        return DB::table(config('session.table', 'sessions'))->where('user_id', $user->getKey());
    }
}
