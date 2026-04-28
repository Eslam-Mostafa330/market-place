<?php

namespace App\Http\Controllers\Api\V1\Rider;

use App\Enums\RiderAvailability;
use App\Enums\UserRole;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Rider\Auth\LoginRequest;
use App\Models\RiderProfile;
use App\Models\User;
use App\Services\Auth\AuthService;
use App\Services\Rider\RiderLocationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends BaseApiController
{
    public function __construct(private readonly AuthService $authService, private readonly RiderLocationService $riderLocationService) {}

    /**
     * Handle the rider login attempts.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $isEmail = filter_var($request->identifier, FILTER_VALIDATE_EMAIL);
        $field   = $isEmail ? 'email' : 'phone';

        $credentials = [
            $field     => $request->identifier,
            'password' => $request->password,
        ];

        $user = $this->authService->attemptLogin($credentials, UserRole::RIDER);

        if ($user) {
            return $this->apiResponse($this->authService->issueTokens($user), __('auth.auth_success'));
        }

        $messageKey = $isEmail ? 'auth_email_failed' : 'auth_phone_failed';
        return $this->apiResponse([], __("auth.{$messageKey}"), 401);
    }

        /**
     * Refresh the access token using the refresh token.
     * Requires the user to be authenticated and have the appropriate ability.
     */
    public function refreshToken(Request $request): JsonResponse
    {
        $result = $this->authService->refresh($request->user());

        return $this->apiResponse($result, __('auth.token_refreshed'));
    }

    /**
    * Logout the user by revoking all their tokens.
    * Requires the user to be authenticated.
    */
    public function logout(Request $request): JsonResponse
    {
        $this->markRiderUnavailable($request->user());
        $this->authService->logout($request->user());
        return $this->apiResponse([], __('auth.logged_out'));
    }

    /**
    * Toggle the authenticated rider availability to unavailable on logout to avoid sending their coordinates.
    */
    private function markRiderUnavailable(User $user): void
    {
        $profile = RiderProfile::where('user_id', $user->id)->first();
        if (! $profile) return;
        $profile->update(['rider_availability' => RiderAvailability::UNAVAILABLE]);
        $this->riderLocationService->removeRiderLocation($profile->id);
    }
}