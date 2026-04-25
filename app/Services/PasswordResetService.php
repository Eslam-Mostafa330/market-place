<?php

namespace App\Services;

use App\Models\User;
use App\Mail\PasswordResetMail;
use App\Models\PasswordReset;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PasswordResetService
{
    private const MAX_ATTEMPTS_PER_EMAIL = 3;
    private const MAX_ATTEMPTS_PER_IP = 10; // IP-based limit to prevent distributed attacks
    private const ATTEMPT_WINDOW_MINUTES = 10;
    private const TOKEN_EXPIRY_MINUTES = 30;

    /**
     * Send a password reset link to the given email address.
     *
     * This method enforces multiple rate-limiting layers:
     * - Per-email request limits
     * - Per-IP request limits
     * - Cooldown for rapid-fire requests
     *
     * If the email exists, a new reset token is created,
     * the old tokens are deleted, and an email with the
     * reset link is sent. If the email does not exist,
     * it pretends success to avoid information leaks.
     *
     * @param string      $email      The user's email address
     * @param string|null $ipAddress  The requester's IP address (for IP-based rate limiting)
     *
     * @return bool  True if the reset link request was accepted
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException If too many requests in a short time
     */
    public function sendResetLink(string $email, ?string $ipAddress = null): bool
    {
        if ($this->hasRecentToken($email)) {
            throw new HttpException(429, __('auth.too_recent_request'));
        }

        if (! $this->canSendResetLink($email, $ipAddress)) {
            return false;
        }

        $user = User::where('email', $email)->first();
        $this->trackResetAttempt($email, $ipAddress);

        if (! $user) {
            return true;
        }

        $token = $this->createResetToken($email);
        $resetUrl = $this->buildResetUrl($token);

        Mail::to($email)->queue(new PasswordResetMail($resetUrl, $user->name));

        return true;
    }

    /**
     * Reset a user's password using a valid reset token.
     *
     * Finds a reset record for the token, validates expiration,
     * updates the user's password, deletes all active login tokens
     * (forcing logout on all devices), and deletes the used reset token.
     *
     * @param string $token        The reset token to validate
     * @param string $newPassword  The new password to set for the user
     *
     * @return string|null  The email of the user whose password was reset, or null if invalid
     */
    public function resetPasswordWithToken(string $token, string $newPassword): ?string
    {
        $resetRecord = $this->findValidResetRecord($token);
        
        if (! $resetRecord) {
            return null;
        }

        $user = User::where('email', $resetRecord->email)->first();
        
        if (! $user) {
            $resetRecord->delete();
            return null;
        }

        $user->update(['password' => $newPassword]);
        $user->tokens()->delete();
        $resetRecord->delete();
        
        return $user->email;
    }

    /**
     * Determine if a reset link can be sent for the given email and IP address.
     *
     * Enforces the configured rate limits per email and per IP.
     *
     * @param string      $email      The email being checked
     * @param string|null $ipAddress  The requester's IP address (optional)
     *
     * @return bool  True if sending is allowed, false if blocked
     */
    private function canSendResetLink(string $email, ?string $ipAddress): bool
    {
        if (Cache::get($this->getEmailAttemptKey($email), 0) >= self::MAX_ATTEMPTS_PER_EMAIL) {
            return false;
        }

        if ($ipAddress && Cache::get($this->getIpAttemptKey($ipAddress), 0) >= self::MAX_ATTEMPTS_PER_IP) {
            return false;
        }

        return true;
    }

    /**
     * Record a password reset attempt for rate-limiting purposes.
     *
     * Tracks attempts by both email and IP address.
     *
     * @param string      $email      The email for which the attempt is made
     * @param string|null $ipAddress  The requester's IP address (optional)
     *
     * @return void
     */
    private function trackResetAttempt(string $email, ?string $ipAddress): void
    {
        $emailKey = $this->getEmailAttemptKey($email);
        Cache::add($emailKey, 0, now()->addMinutes(self::ATTEMPT_WINDOW_MINUTES));
        Cache::increment($emailKey);

        if ($ipAddress) {
            $ipKey = $this->getIpAttemptKey($ipAddress);
            Cache::add($ipKey, 0, now()->addMinutes(self::ATTEMPT_WINDOW_MINUTES));
            Cache::increment($ipKey);
        }
    }

    /**
     * Check if the given email has a recently generated reset token.
     *
     * Prevents issuing multiple tokens within a short cooldown window.
     *
     * @param string $email  The email to check
     *
     * @return bool  True if a recent token exists, false otherwise
     */
    private function hasRecentToken(string $email): bool
    {
        return PasswordReset::where('email', $email)
            ->where('created_at', '>', now()->subMinutes(1))
            ->exists();
    }

    /**
     * Create a new password reset token for the given email.
     *
     * Deletes any previous tokens for the email, generates a
     * random 64-character token, and stores it with an expiry time.
     *
     * @param string $email  The email address for which to create the token
     *
     * @return string  The generated reset token
     */
    private function createResetToken(string $email): string
    {
        PasswordReset::where('email', $email)->delete();
        
        $token = Str::random(64);
        
        PasswordReset::create([
            'email'      => $email,
            'token'      => hash('sha256', $token),
            'expires_at' => now()->addMinutes(self::TOKEN_EXPIRY_MINUTES),
        ]);
        
        return $token;
    }

    /**
     * Find a valid (non-expired) password reset record by token.
     *
     * @param string $token  The reset token to look up
     *
     * @return PasswordReset|null  The valid reset record, or null if not found or expired
     */
    private function findValidResetRecord(string $token): ?PasswordReset
    {
        return PasswordReset::where('token', hash('sha256', $token))
            ->where('expires_at', '>', now())
            ->first();
    }

    /**
     * Build the reset URL that will be sent to the user.
     *
     * Displays in the app URL. The token is appended to the reset path.
     *
     * @param string $token  The reset token
     *
     * @return string  The full reset URL
     */
    private function buildResetUrl(string $token): string
    {
        $baseUrl = config('app.frontend_url') ?? config('app.url');
        return "{$baseUrl}/reset-password/{$token}";
    }

    /**
     * Generate the cache key used for per-email reset attempt tracking.
     *
     * @param string $email  The email to generate a key for
     *
     * @return string  The generated cache key
     */
    private function getEmailAttemptKey(string $email): string
    {
        return "reset_email_{$email}";
    }

    /**
     * Generate the cache key used for per-IP reset attempt tracking.
     *
     * @param string $ipAddress  The IP address to generate a key for
     *
     * @return string  The generated cache key
     */
    private function getIpAttemptKey(string $ipAddress): string
    {
        return "reset_ip_{$ipAddress}";
    }
}