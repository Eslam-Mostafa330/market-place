<?php

use App\Models\PasswordReset;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

beforeEach(function () {
    Mail::fake();
    cache()->flush();

    $this->customer = User::factory()->customer()->create([
        'email'    => 'customer@example.com',
        'password' => Hash::make('Old!Passw0rd'),
    ]);
});

/**
 * Seed a reset token, returning the plaintext the user would receive by email.
 */
function issueResetToken(string $email, ?Carbon\CarbonInterface $expiresAt = null): string
{
    $token = Str::random(64);

    PasswordReset::create([
        'email'      => $email,
        'token'      => hash('sha256', $token),
        'expires_at' => $expiresAt ?? now()->addHour(),
    ]);

    return $token;
}

it('sends a reset link to a known customer', function () {
    $this->postJson('/api/v1/customer/auth/password/forgot', ['email' => 'customer@example.com'])
        ->assertOk();

    expect(PasswordReset::where('email', 'customer@example.com')->exists())->toBeTrue();
});

it('rejects an unknown email at validation', function () {
    $this->postJson('/api/v1/customer/auth/password/forgot', ['email' => 'nobody@example.com'])
        ->assertApiValidationErrors('email');
});

/**
 * The forgot endpoint is scoped to customers, so a vendor address must not be
 * able to obtain a customer reset link.
 */
it('does not send a reset link for a non-customer account', function () {
    User::factory()->vendor()->create(['email' => 'vendor@example.com']);

    $this->postJson('/api/v1/customer/auth/password/forgot', ['email' => 'vendor@example.com'])
        ->assertApiValidationErrors('email');

    expect(PasswordReset::where('email', 'vendor@example.com')->exists())->toBeFalse();
});

it('resets the password with a valid token', function () {
    $token = issueResetToken('customer@example.com');

    $this->postJson('/api/v1/customer/auth/password/reset', [
        'token'                 => $token,
        'password'              => 'Brand!NewPass1',
        'password_confirmation' => 'Brand!NewPass1',
    ])->assertOk();

    expect(Hash::check('Brand!NewPass1', $this->customer->fresh()->password))->toBeTrue();
});

it('consumes the reset token so it cannot be reused', function () {
    $token = issueResetToken('customer@example.com');

    $payload = [
        'token'                 => $token,
        'password'              => 'Brand!NewPass1',
        'password_confirmation' => 'Brand!NewPass1',
    ];

    $this->postJson('/api/v1/customer/auth/password/reset', $payload)->assertOk();
    $this->postJson('/api/v1/customer/auth/password/reset', $payload)->assertStatus(400);

    expect(PasswordReset::count())->toBe(0);
});

it('rejects an expired reset token', function () {
    $token = issueResetToken('customer@example.com', now()->subMinute());

    $this->postJson('/api/v1/customer/auth/password/reset', [
        'token'                 => $token,
        'password'              => 'Brand!NewPass1',
        'password_confirmation' => 'Brand!NewPass1',
    ])->assertStatus(400);

    expect(Hash::check('Old!Passw0rd', $this->customer->fresh()->password))->toBeTrue();
});

it('rejects an unknown reset token', function () {
    $this->postJson('/api/v1/customer/auth/password/reset', [
        'token'                 => Str::random(64),
        'password'              => 'Brand!NewPass1',
        'password_confirmation' => 'Brand!NewPass1',
    ])->assertStatus(400);
});

it('requires the new password to be confirmed', function () {
    $token = issueResetToken('customer@example.com');

    $this->postJson('/api/v1/customer/auth/password/reset', [
        'token'                 => $token,
        'password'              => 'Brand!NewPass1',
        'password_confirmation' => 'Different!Pass1',
    ])->assertApiValidationErrors('password');
});

it('rejects a weak new password', function () {
    $token = issueResetToken('customer@example.com');

    $this->postJson('/api/v1/customer/auth/password/reset', [
        'token'                 => $token,
        'password'              => 'abc',
        'password_confirmation' => 'abc',
    ])->assertApiValidationErrors('password');
});

/**
 * A reset is a credential change, so every existing session must be cut off —
 * otherwise an attacker who prompted the reset keeps their access.
 */
it('revokes existing tokens after a reset', function () {
    $this->customer->createToken('AccessToken');
    expect($this->customer->tokens()->count())->toBe(1);

    $token = issueResetToken('customer@example.com');

    $this->postJson('/api/v1/customer/auth/password/reset', [
        'token'                 => $token,
        'password'              => 'Brand!NewPass1',
        'password_confirmation' => 'Brand!NewPass1',
    ])->assertOk();

    expect($this->customer->fresh()->tokens()->count())->toBe(0);
});

it('lets the customer log in with the new password', function () {
    $token = issueResetToken('customer@example.com');

    $this->postJson('/api/v1/customer/auth/password/reset', [
        'token'                 => $token,
        'password'              => 'Brand!NewPass1',
        'password_confirmation' => 'Brand!NewPass1',
    ])->assertOk();

    $this->app['auth']->forgetGuards();

    $this->postJson('/api/v1/customer/auth/login', [
        'identifier' => 'customer@example.com',
        'password'   => 'Brand!NewPass1',
    ])->assertOk();
});
