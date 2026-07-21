<?php

use App\Enums\DefineStatus;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
    cache()->flush();

    $this->customer = User::factory()->customer()->create([
        'email'    => 'customer@example.com',
        'phone'    => '01099887766',
        'password' => Hash::make('Str0ng!Passw0rd'),
    ]);
});

function login(array $payload): Illuminate\Testing\TestResponse
{
    return test()->postJson('/api/v1/customer/auth/login', $payload);
}

it('logs in with an email', function () {
    login(['identifier' => 'customer@example.com', 'password' => 'Str0ng!Passw0rd'])
        ->assertOk()
        ->assertJsonStructure(['data' => ['access_token', 'refresh_token', 'user']]);
});

it('logs in with a phone number', function () {
    login(['identifier' => '01099887766', 'password' => 'Str0ng!Passw0rd'])
        ->assertOk()
        ->assertJsonStructure(['data' => ['access_token', 'refresh_token']]);
});

it('issues distinct access and refresh tokens', function () {
    $response = login(['identifier' => 'customer@example.com', 'password' => 'Str0ng!Passw0rd'])->assertOk();

    expect($response->json('data.access_token'))->not->toBe($response->json('data.refresh_token'))
        ->and($this->customer->tokens()->count())->toBe(2);
});

it('rejects a wrong password', function () {
    login(['identifier' => 'customer@example.com', 'password' => 'WrongPassword1!'])
        ->assertStatus(401);
});

it('rejects an unknown identifier', function () {
    login(['identifier' => 'nobody@example.com', 'password' => 'Str0ng!Passw0rd'])
        ->assertStatus(401);
});

/**
 * Ownership of the address must be proven before the account is usable.
 */
it('rejects an unverified account', function () {
    $this->customer->update(['email_verified_at' => null]);

    login(['identifier' => 'customer@example.com', 'password' => 'Str0ng!Passw0rd'])
        ->assertStatus(403);
});

it('rejects a deactivated account', function () {
    $this->customer->update(['status' => DefineStatus::INACTIVE]);

    login(['identifier' => 'customer@example.com', 'password' => 'Str0ng!Passw0rd'])
        ->assertStatus(403);
});

/**
 * The customer endpoint must not authenticate accounts from other roles, even
 * with correct credentials.
 */
it('rejects a non-customer account on the customer endpoint', function () {
    User::factory()->vendor()->create([
        'email'    => 'vendor@example.com',
        'password' => Hash::make('Str0ng!Passw0rd'),
    ]);

    login(['identifier' => 'vendor@example.com', 'password' => 'Str0ng!Passw0rd'])
        ->assertStatus(401);
});

it('requires both credentials', function (string $field) {
    $payload = ['identifier' => 'customer@example.com', 'password' => 'Str0ng!Passw0rd'];
    $payload[$field] = '';

    login($payload)->assertApiValidationErrors($field);
})->with(['identifier', 'password']);

/**
 * The auth limiter allows 6 attempts per minute per IP.
 */
it('throttles repeated failed attempts', function () {
    foreach (range(1, 6) as $attempt) {
        login(['identifier' => 'customer@example.com', 'password' => 'WrongPassword1!']);
    }

    login(['identifier' => 'customer@example.com', 'password' => 'Str0ng!Passw0rd'])
        ->assertStatus(429);
});
