<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Admin\Auth\ResendOtpRequest;
use App\Http\Requests\Admin\Auth\VerifyOtpRequest;
use App\Services\Auth\AuthService;
use App\Services\Auth\TwoFactorService;
use Illuminate\Http\JsonResponse;

class TwoFactorController extends BaseApiController
{
    public function __construct(private readonly TwoFactorService $twoFactorService, private readonly AuthService $authService) {}

    /**
     * Verify the OTP code and issue tokens on success.
     */
    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $user = $this->twoFactorService->verifyOtp($request->temp_token, $request->code);

        $this->twoFactorService->trustDevice($user->id, $request);

        return $this->apiResponse($this->authService->issueTokens($user), __('auth.auth_success'));
    }

    /**
     * Resend a fresh OTP to the user's email.
     */
    public function resendOtp(ResendOtpRequest $request): JsonResponse
    {
        $user = $this->twoFactorService->getUserByTempToken($request->temp_token);

        return $this->apiResponse(['temp_token' => $this->twoFactorService->sendOtp($user)], __('auth.otp_resent'));
    }
}
