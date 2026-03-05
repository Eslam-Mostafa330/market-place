<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Enums\DefineStatus;
use App\Enums\UserRole;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Vendor\Auth\LoginRequest;
use App\Http\Requests\Vendor\Auth\RegisterRequest;
use App\Http\Resources\Vendor\Auth\RegisterResource;
use App\Models\User;
use App\Services\AuthService;
use App\Services\EmailVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends BaseApiController
{
    public function __construct(
        private readonly AuthService              $authService,
        private readonly EmailVerificationService $verificationService,
    ) {}

    /**
     * Handle vendor registration.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $vendorData = $request->validated();
        $vendorData['role'] = UserRole::VENDOR;
        $vendorData['status'] = DefineStatus::INACTIVE;
        $vendor = User::create($vendorData);
        $this->verificationService->sendVerificationEmail($vendor, $request->ip());

        return $this->apiResponseStored(new RegisterResource($vendor));
    }

    /**
     * Handle login attempts.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        $result = $this->authService->attemptLogin($credentials, UserRole::VENDOR);

        if (! $result) {
            return $this->apiResponse([], __('auth.auth_failed'), 401);
        }

        return $this->apiResponse($result, __('auth.auth_success'));
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