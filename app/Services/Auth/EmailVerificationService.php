<?php

namespace App\Services\Auth;

use App\Mail\EmailVerificationMail;
use App\Models\User;
use App\Models\EmailVerification;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;


class EmailVerificationService
{
    private const MAX_ATTEMPTS_PER_EMAIL = 3;
    private const MAX_ATTEMPTS_PER_IP    = 10; // IP-based limit to prevent distributed attacks
    private const ATTEMPT_WINDOW_MINUTES = 10;
    private const TOKEN_EXPIRY_MINUTES   = 60;

    /**
     * Send a verification email to the given user.
     *
     * This method enforces multiple rate-limiting layers:
     * - Per-email request limits
     * - Per-IP request limits
     * - Cooldown for rapid-fire requests
     *
     * A new verification token is created, any old tokens are deleted,
     * and an email with the verification link is sent.
     *
     * @param User        $user       The user to verify
     * @param string|null $ipAddress  The requester's IP address (for IP-based rate limiting)
     *
     * @return bool  True if the verification email was sent
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException  If too many requests in a short time
     */
    public function sendVerificationEmail(User $user, ?string $ipAddress = null): bool
    {
        $this->ensureNotRateLimited($user->email, $ipAddress);

        if ($this->hasRecentToken($user->email)) {
            throw new HttpException(429, __('auth.too_recent_request'));
        }

        RateLimiter::hit($this->emailKey($user->email), self::ATTEMPT_WINDOW_MINUTES * 60);

        if ($ipAddress) {
            RateLimiter::hit($this->ipKey($ipAddress), self::ATTEMPT_WINDOW_MINUTES * 60);
        }

        $token     = $this->createToken($user->email);
        $verifyUrl = $this->buildVerifyUrl($token);

        Mail::to($user->email)->queue(new EmailVerificationMail($verifyUrl, $user->name));

        return true;
    }

    /**
     * Verify a user's email using a valid verification token.
     *
     * Finds a verification record for the token, validates expiration,
     * sets email_verified_at on the user, and deletes the used token.
     *
     * @param string $token  The verification token to validate
     *
     * @return bool  True if the email was successfully verified, false if token is invalid or expired
     */
    public function verifyEmail(string $token): bool
    {
        $record = $this->findValidRecord($token);

        if (! $record) {
            return false;
        }

        User::where('email', $record->email)->update(['email_verified_at' => now()]);

        $record->delete();

        return true;
    }

    /**
     * Ensure the request is not rate limited by email or IP address.
     *
     * @param string      $email      The email being checked
     * @param string|null $ipAddress  The requester's IP address (optional)
     *
     * @return void
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException  If either rate limit is exceeded
     */
    private function ensureNotRateLimited(string $email, ?string $ipAddress): void
    {
        if (RateLimiter::tooManyAttempts($this->emailKey($email), self::MAX_ATTEMPTS_PER_EMAIL)) {
            $minutes = ceil(RateLimiter::availableIn($this->emailKey($email)) / 60);
            throw new HttpException(429, __('auth.too_many_attempts', ['minutes' => $minutes]));
        }

        if ($ipAddress && RateLimiter::tooManyAttempts($this->ipKey($ipAddress), self::MAX_ATTEMPTS_PER_IP)) {
            throw new HttpException(429, __('auth.too_many_attempts_ip'));
        }
    }

    /**
     * Check if the given email has a recently generated verification token.
     *
     * Prevents issuing multiple tokens within a short cooldown window.
     *
     * @param string $email  The email to check
     *
     * @return bool  True if a recent token exists, false otherwise
     */
    private function hasRecentToken(string $email): bool
    {
        return EmailVerification::where('email', $email)
            ->where('created_at', '>', now()->subMinutes(1))
            ->exists();
    }

    /**
     * Create a new verification token for the given email.
     *
     * Deletes any previous tokens for the email, generates a
     * random 64-character token, stores its SHA-256 hash, and returns
     * the plaintext token to be embedded in the verification URL.
     *
     * @param string $email  The email address for which to create the token
     *
     * @return string  The generated plaintext token
     */
    private function createToken(string $email): string
    {
        EmailVerification::where('email', $email)->delete();

        $token = Str::random(64);

        EmailVerification::create([
            'email'      => $email,
            'token'      => hash('sha256', $token),
            'expires_at' => now()->addMinutes(self::TOKEN_EXPIRY_MINUTES),
        ]);

        return $token;
    }

    /**
     * Find a valid (non-expired) verification record by token.
     *
     * @param string $token  The plaintext token to look up
     *
     * @return EmailVerification|null  The valid record, or null if not found or expired
     */
    private function findValidRecord(string $token): ?EmailVerification
    {
        return EmailVerification::where('token', hash('sha256', $token))
            ->where('expires_at', '>', now())
            ->first();
    }

    /**
     * Build the verification URL that will be sent to the user.
     *
     * @param string $token  The plaintext token
     *
     * @return string  The full verification URL
     */
    private function buildVerifyUrl(string $token): string
    {
        $baseUrl = config('app.frontend_url') ?? config('app.url');
        return "{$baseUrl}/verify-email/{$token}";
    }

    /**
     * Generate the cache key used for per-email verification attempt tracking.
     *
     * @param string $email  The email to generate a key for
     *
     * @return string  The generated cache key
     */
    private function emailKey(string $email): string
    {
        return "verify_email_{$email}";
    }

    /**
     * Generate the cache key used for per-IP verification attempt tracking.
     *
     * @param string $ipAddress  The IP address to generate a key for
     *
     * @return string  The generated cache key
     */
    private function ipKey(string $ipAddress): string
    {
        return "verify_ip_{$ipAddress}";
    }
}