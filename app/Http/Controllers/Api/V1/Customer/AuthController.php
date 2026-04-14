<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Enums\UserRole;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Customer\Auth\LoginRequest;
use App\Http\Requests\Customer\Auth\RegisterRequest;
use App\Http\Resources\Customer\Auth\RegisterResource;
use App\Models\User;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuthController extends BaseApiController
{
    public function __construct(private readonly AuthService $authService) {}

    /**
     * Handle customer registration.
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $customer = DB::transaction(function () use ($request) {
            $customerData = $request->validated();
            $customerData['role'] = UserRole::CUSTOMER;
            $customer = User::create($customerData);
            $customer->customerProfile()->create();
            return $customer;
        });

        return $this->apiResponseStored(new RegisterResource($customer));
    }

    /**
     * Handle the customer login attempts.
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $isEmail = filter_var($request->identifier, FILTER_VALIDATE_EMAIL);
        $field   = $isEmail ? 'email' : 'phone';

        $credentials = [
            $field     => $request->identifier,
            'password' => $request->password,
        ];

        $user = $this->authService->attemptLogin($credentials, UserRole::CUSTOMER);

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
        $this->authService->logout($request->user());
        return $this->apiResponse([], __('auth.logged_out'));
    }
}