<?php

use App\Models\EmailVerification;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

beforeEach(function () {
    Mail::fake();

    $this->customer = User::factory()->customer()->create([
        'email'             => 'customer@example.com',
        'email_verified_at' => null,
    ]);

    EmailVerification::query()->delete();
    cache()->flush();
});

/**
 * Seed a verification token, returning the plaintext the user would receive.
 * Only the sha256 hash is stored, so tests cannot read it back out.
 */
function issueVerificationToken(string $email, ?Carbon\CarbonInterface $expiresAt = null): string
{
    $token = Str::random(64);

    EmailVerification::create([
        'email'      => $email,
        'token'      => hash('sha256', $token),
        'expires_at' => $expiresAt ?? now()->addHour(),
    ]);

    return $token;
}

it('verifies an email with a valid token', function () {
    $token = issueVerificationToken('customer@example.com');

    $this->postJson('/api/v1/customer/auth/email/verify', ['token' => $token])->assertOk();

    expect($this->customer->fresh()->email_verified_at)->not->toBeNull();
});

it('consumes the token so it cannot be reused', function () {
    $token = issueVerificationToken('customer@example.com');

    $this->postJson('/api/v1/customer/auth/email/verify', ['token' => $token])->assertOk();
    $this->postJson('/api/v1/customer/auth/email/verify', ['token' => $token])->assertStatus(422);

    expect(EmailVerification::count())->toBe(0);
});

it('rejects an expired token', function () {
    $token = issueVerificationToken('customer@example.com', now()->subMinute());

    $this->postJson('/api/v1/customer/auth/email/verify', ['token' => $token])->assertStatus(422);

    expect($this->customer->fresh()->email_verified_at)->toBeNull();
});

it('rejects an unknown token', function () {
    $this->postJson('/api/v1/customer/auth/email/verify', ['token' => Str::random(64)])
        ->assertStatus(422);

    expect($this->customer->fresh()->email_verified_at)->toBeNull();
});

it('rejects a malformed token', function () {
    $this->postJson('/api/v1/customer/auth/email/verify', ['token' => 'too-short'])
        ->assertApiValidationErrors('token');
});

it('resends the verification email', function () {
    $this->postJson('/api/v1/customer/auth/email/resend', ['email' => 'customer@example.com'])
        ->assertOk();

    expect(EmailVerification::where('email', 'customer@example.com')->exists())->toBeTrue();
});

it('does not resend to an already verified account', function () {
    $this->customer->update(['email_verified_at' => now()]);

    $this->postJson('/api/v1/customer/auth/email/resend', ['email' => 'customer@example.com'])
        ->assertStatus(422);
});

/**
 * Issuing a new token invalidates the previous one, so an intercepted older link
 * stops working as soon as the user requests another.
 */
it('replaces any previous token when resending', function () {
    $firstToken = issueVerificationToken('customer@example.com');

    EmailVerification::query()->update(['created_at' => now()->subMinutes(5)]);

    $this->postJson('/api/v1/customer/auth/email/resend', ['email' => 'customer@example.com'])
        ->assertOk();

    expect(EmailVerification::where('email', 'customer@example.com')->count())->toBe(1);

    $this->postJson('/api/v1/customer/auth/email/verify', ['token' => $firstToken])
        ->assertStatus(422);
});
