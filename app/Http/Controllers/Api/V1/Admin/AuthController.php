<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends BaseApiController
{
    public function __construct(
        private readonly AuthService $authService,
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        $result = $this->authService->attemptLogin($credentials, UserRole::ADMIN);

        if (! $result) {
            return $this->apiResponse([], __('auth.auth_failed'), 401);
        }

        return $this->apiResponse($result, __('auth.auth_success'));
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return $this->apiResponse([], __('auth.logged_out'));
    }

    public function refreshToken(Request $request): JsonResponse
    {
        $result = $this->authService->refresh($request->user());

        return $this->apiResponse($result, __('auth.token_refreshed'));
    }
}
