<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();

    cache()->flush();
});

function registrationPayload(array $overrides = []): array
{
    return [
        'name'                  => 'Test Customer',
        'email'                 => 'customer@example.com',
        'phone'                 => '01012345678',
        'password'              => 'Str0ng!Passw0rd',
        'password_confirmation' => 'Str0ng!Passw0rd',
        ...$overrides,
    ];
}

it('registers a customer', function () {
    $response = $this->postJson('/api/v1/customer/auth/register', registrationPayload());

    $response->assertCreated();

    $user = User::where('email', 'customer@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->role)->toBe(UserRole::CUSTOMER);
});

it('creates a customer profile alongside the account', function () {
    $this->postJson('/api/v1/customer/auth/register', registrationPayload())->assertCreated();

    $user = User::where('email', 'customer@example.com')->first();

    expect($user->customerProfile)->not->toBeNull();
});

it('hashes the password', function () {
    $this->postJson('/api/v1/customer/auth/register', registrationPayload())->assertCreated();

    $user = User::where('email', 'customer@example.com')->first();

    expect($user->password)->not->toBe('Str0ng!Passw0rd')
        ->and(Hash::check('Str0ng!Passw0rd', $user->password))->toBeTrue();
});

/**
 * A new account must prove ownership of the address before it can be used.
 */
it('leaves a new account unverified', function () {
    $this->postJson('/api/v1/customer/auth/register', registrationPayload())->assertCreated();

    expect(User::where('email', 'customer@example.com')->first()->email_verified_at)->toBeNull();
});

it('rejects a duplicate email', function () {
    User::factory()->customer()->create(['email' => 'customer@example.com']);

    $this->postJson('/api/v1/customer/auth/register', registrationPayload())
        ->assertApiValidationErrors('email');
});

it('rejects a duplicate phone', function () {
    User::factory()->customer()->create(['phone' => '01012345678']);

    $this->postJson('/api/v1/customer/auth/register', registrationPayload())
        ->assertApiValidationErrors('phone');
});

it('rejects an unconfirmed password', function () {
    $this->postJson('/api/v1/customer/auth/register', registrationPayload([
        'password_confirmation' => 'Different!Passw0rd',
    ]))
        ->assertApiValidationErrors('password');
});

it('rejects a weak password', function () {
    $this->postJson('/api/v1/customer/auth/register', registrationPayload([
        'password'              => 'abc',
        'password_confirmation' => 'abc',
    ]))
        ->assertApiValidationErrors('password');
});

it('requires the mandatory fields', function (string $field) {
    $this->postJson('/api/v1/customer/auth/register', registrationPayload([$field => '']))
        ->assertApiValidationErrors($field);
})->with(['name', 'email', 'phone', 'password']);

it('rejects a malformed email', function () {
    $this->postJson('/api/v1/customer/auth/register', registrationPayload(['email' => 'not-an-email']))
        ->assertApiValidationErrors('email');
});
