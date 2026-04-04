<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Admin\Auth\LoginRequest;
use App\Services\AuthService;
use App\Services\TwoFactorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends BaseApiController
{
    public function __construct(
        private readonly AuthService      $authService,
        private readonly TwoFactorService $twoFactorService,
    ) {}

    /**
     * Handle the admin login attempts, including 2FA checks and OTP sending if required.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();
        $user = $this->authService->attemptLogin($credentials, UserRole::ADMIN);

        if (! $user) {
            return $this->apiResponse([], __('auth.auth_failed'), 401);
        }

        if ($this->twoFactorService->isRequired($user, $request)) {
            return $this->apiResponse([
                'temp_token' => $this->twoFactorService->sendOtp($user),
            ]);
        }

        return $this->apiResponse($this->authService->issueTokens($user), __('auth.auth_success'));
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
        $this->authService->logout($request->user());

        return $this->apiResponse([], __('auth.logged_out'));
    }
}