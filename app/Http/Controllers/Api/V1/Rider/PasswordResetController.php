<?php

namespace App\Http\Controllers\Api\V1\Rider;

use App\Enums\UserRole;
use App\Http\Controllers\Api\BaseApiController;
use App\Http\Requests\Rider\Auth\ForgotPasswordRequest;
use App\Http\Requests\Rider\Auth\ResetPasswordRequest;
use App\Models\User;
use App\Services\Auth\PasswordResetService;
use Illuminate\Http\JsonResponse;

class PasswordResetController extends BaseApiController
{
    public function __construct(private readonly PasswordResetService $passwordResetService) {}

    /**
     * Send a password reset link to the user's email.
     *
     * Validates the request, extracts the email and IP address,
     * Applies rate limiting via the PasswordResetService.
     * Returns 200 if accepted, 429 if too many requests.
     *
     * @param ForgotPasswordRequest $request
     * @param PasswordResetService  $passwordResetService
     *
     * @return JsonResponse
     */
    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $userEmail = $request->validated()['email'];
        $user = User::where('email', $userEmail)->first();

        if (! $user || $user->role !== UserRole::RIDER) {
            return $this->apiResponse([], __('auth.auth_failed'), 401);
        }

        $ipAddress = $request->ip();
        $wasLinkSent = $this->passwordResetService->sendResetLink($userEmail, $ipAddress);

        return $wasLinkSent
            ? $this->apiResponse(__('auth.reset_link_sent'))
            : $this->apiResponse([], __('auth.too_many_requests'), 429);
    }

    /**
     * Reset the user's password using a valid token.
     *
     * Returns 200 if successful, or 400 if the token is invalid/expired.
     *
     * @param ResetPasswordRequest $request
     * @param PasswordResetService $passwordResetService
     *
     * @return JsonResponse
     */
    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $token = $request->validated()['token'];
        $newPassword = $request->validated()['password'];
        
        $emailForReset = $this->passwordResetService->resetPasswordWithToken($token, $newPassword);

        return $emailForReset
            ? $this->apiResponse(__('auth.password_reset_success'))
            : $this->apiResponse([], __('auth.reset_link_invalid'), 400);
    }
}