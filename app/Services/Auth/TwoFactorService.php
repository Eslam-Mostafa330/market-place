<?php

namespace App\Services\Auth;

use App\Mail\TwoFactorCodeMail;
use App\Models\TrustedDevice;
use App\Models\TwoFactorCode;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class TwoFactorService
{
    /**
     * Determine if 2FA is required for the given user on the current device.
     * Returns false if the user's role has 2FA disabled in config,
     * or if the device is already trusted.
     */
    public function isRequired($user, Request $request): bool
    {
        if (! in_array($user->role->value, config('two_factor.enabled_for_roles'))) {
            return false;
        }

        return ! $this->isDeviceTrusted($user->id, $this->fingerprint($request));
    }

    /**
     * Generate and send a fresh OTP to the user's email.
     * Enforces two rate limits before sending:
     *   - Max 3 OTP sends per 10-minute window (per user)
     *   - 1 minute cooldown between each send
     * Returns the temp token to be held by the frontend.
     */
    public function sendOtp($user): string
    {
        $this->checkSendRateLimits($user->id);

        $code      = random_int(100000, 999999);
        $tempToken = Str::random(64);

        TwoFactorCode::where('user_id', $user->id)->delete();

        TwoFactorCode::create([
            'user_id'    => $user->id,
            'code'       => Hash::make($code),
            'temp_token' => $tempToken,
            'expires_at' => now()->addMinutes(config('two_factor.otp_expires_in_minutes')),
        ]);

        Mail::to($user->email)->queue(new TwoFactorCodeMail($code, $user->name));

        return $tempToken;
    }

    /**
     * Verify the submitted OTP code against the stored hashed value.
     * Enforces max 3 wrong attempts per temp token before locking.
     * Clears the rate limiter and deletes the record on success.
     * Throws 429 if too many attempts, 401 if invalid or expired.
     */
    public function verifyOtp(string $tempToken, string $code): object
    {
        $rateKey = "otp_verify_{$tempToken}";

        if (RateLimiter::tooManyAttempts($rateKey, config('two_factor.max_attempts'))) {
            throw new HttpException(429, __('auth.otp_max_attempts'));
        }

        $record = TwoFactorCode::where('temp_token', $tempToken)
            ->where('expires_at', '>', now())
            ->first();

        if (! $record || ! Hash::check($code, $record->code)) {
            RateLimiter::hit($rateKey, config('two_factor.otp_expires_in_minutes') * 60);
            throw new HttpException(401, __('auth.otp_invalid'));
        }

        RateLimiter::clear($rateKey);

        $user = $record->user;
        $record->delete();

        return $user;
    }

    /**
     * Mark the current device as trusted for the configured number of days.
     * Uses a fingerprint derived from the request IP and User-Agent.
     */
    public function trustDevice(string $userId, Request $request): void
    {
        TrustedDevice::updateOrCreate(
            [
                'user_id'            => $userId,
                'device_fingerprint' => $this->fingerprint($request),
            ],
            [
                'trusted_until' => now()->addDays(config('two_factor.device_trusted_for_days')),
            ]
        );
    }

    /**
     * Retrieve the user associated with a valid temp token.
     * Used by the resend endpoint to identify the user without authentication.
     * Throws 401 if the token is missing or expired.
     */
    public function getUserByTempToken(string $tempToken): object
    {
        $record = TwoFactorCode::where('temp_token', $tempToken)
            ->where('expires_at', '>', now())
            ->first();

        if (! $record) {
            throw new HttpException(401, __('auth.otp_invalid'));
        }

        return $record->user;
    }

    /**
     * Enforce rate limits before sending an OTP:
     *   - 3 sends max per 10-minute window (otp_limit)
     *   - 1 minute cooldown between sends (otp_cooldown)
     * Throws 429 with a descriptive message if either limit is hit.
     */
    private function checkSendRateLimits(string $userId): void
    {
        $limitKey    = "otp_limit_{$userId}";
        $cooldownKey = "otp_cooldown_{$userId}";

        if (RateLimiter::tooManyAttempts($limitKey, config('two_factor.max_attempts'))) {
            throw new HttpException(429, __('auth.otp_max_attempts'));
        }

        if (RateLimiter::tooManyAttempts($cooldownKey, 1)) {
            $seconds = RateLimiter::availableIn($cooldownKey);
            throw new HttpException(429, __('auth.otp_throttle', ['seconds' => $seconds]));
        }

        RateLimiter::hit($limitKey, config('two_factor.otp_expires_in_minutes') * 60);
        RateLimiter::hit($cooldownKey, config('two_factor.attempt_cooldown_minutes') * 60);
    }

    /**
     * Check if the given device fingerprint is trusted for the user.
     */
    private function isDeviceTrusted(string $userId, string $fingerprint): bool
    {
        return TrustedDevice::where('user_id', $userId)
            ->where('device_fingerprint', $fingerprint)
            ->where('trusted_until', '>', now())
            ->exists();
    }

    /**
     * Generate a consistent device fingerprint from the request.
     * Hashes the IP address and User-Agent together.
     */
    private function fingerprint(Request $request): string
    {
        return hash('sha256', $request->ip() . '|' . $request->userAgent());
    }
}