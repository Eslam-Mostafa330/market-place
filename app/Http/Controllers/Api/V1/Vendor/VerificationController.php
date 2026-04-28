<?php

namespace App\Http\Controllers\Api\V1\Vendor;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Vendor\Auth\ResendVerificationRequest;
use App\Http\Requests\Vendor\Auth\VerifyEmailRequest;
use App\Models\User;
use App\Services\Auth\EmailVerificationService;
use Illuminate\Http\JsonResponse;

class VerificationController extends BaseApiController
{
    public function __construct(private readonly EmailVerificationService $verificationService) {}

    /**
     * Verify a user's email using a valid verification token.
     * This endpoint is accessed when the user clicks the verification link in their email.
     */
    public function verify(VerifyEmailRequest $request): JsonResponse
    {
        $verified = $this->verificationService->verifyEmail($request->token);

        return $verified
            ? $this->apiResponse(__('auth.email_verified'))
            : $this->apiResponse([], __('auth.invalid_or_expired_token'), 422);
    }

    /**
     * Resend the email verification link to the user.
     * This endpoint allows users to request a new verification email if they haven't received the original one
     */
    public function resend(ResendVerificationRequest $request): JsonResponse
    {
        $user = User::where('email', $request->email)->first();

        if ($user->email_verified_at) {
            return $this->apiResponse([], __('auth.email_already_verified'), 422);
        }

        $this->verificationService->sendVerificationEmail($user, $request->ip());

        return $this->apiResponse(__('auth.verification_sent'));
    }
}
