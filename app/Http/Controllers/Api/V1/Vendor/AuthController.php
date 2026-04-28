<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Enums\UserRole;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Vendor\Auth\LoginRequest;
use App\Http\Requests\Vendor\Auth\RegisterRequest;
use App\Http\Resources\Vendor\Auth\RegisterResource;
use App\Models\User;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends BaseApiController
{
    public function __construct(private readonly AuthService $authService) {}

    /**
     * Handle vendor registration.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $vendorData = $request->validated();
        $vendorData['role'] = UserRole::VENDOR;
        $vendor = User::create($vendorData);

        return $this->apiResponseStored(new RegisterResource($vendor));
    }

    /**
     * Handle the vendor login attempts.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();
        $user = $this->authService->attemptLogin($credentials, UserRole::VENDOR);

        return $user
            ? $this->apiResponse($this->authService->issueTokens($user), __('auth.auth_success'))
            : $this->apiResponse([], __('auth.auth_failed'), 401);
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