<?php

use App\Enums\TokenAbility;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Mail::fake();
    cache()->flush();

    $this->customer = User::factory()->customer()->create([
        'email'    => 'customer@example.com',
        'password' => Hash::make('Str0ng!Passw0rd'),
    ]);

    $this->tokens = $this->postJson('/api/v1/customer/auth/login', [
        'identifier' => 'customer@example.com',
        'password'   => 'Str0ng!Passw0rd',
    ])->json('data');

    $this->app['auth']->forgetGuards();
});

/**
 * Return type is left off deliberately: Pest runs each file in a generated
 * subclass of the test case, so the concrete type is not nameable here.
 */
function withToken(string $token)
{
    return test()->withHeader('Authorization', 'Bearer ' . $token);
}

it('accepts the access token on a protected route', function () {
    withToken($this->tokens['access_token'])
        ->getJson('/api/v1/customer/orders')
        ->assertOk();
});

it('rejects a request with no token', function () {
    $this->getJson('/api/v1/customer/orders')->assertUnauthorized();
});

it('rejects a garbage token', function () {
    withToken('not-a-real-token')
        ->getJson('/api/v1/customer/orders')
        ->assertUnauthorized();
});

it('exchanges a refresh token for a new access token', function () {
    $response = withToken($this->tokens['refresh_token'])
        ->postJson('/api/v1/customer/auth/refresh')
        ->assertOk();

    expect($response->json('data.access_token'))->not->toBeNull()
        ->and($response->json('data.access_token'))->not->toBe($this->tokens['access_token']);
});

/**
 * The refresh endpoint is gated on the ISSUE_ACCESS_TOKEN ability, so an ordinary
 * access token must not be able to mint new tokens for itself.
 */
it('does not let an access token be used to refresh', function () {
    withToken($this->tokens['access_token'])
        ->postJson('/api/v1/customer/auth/refresh')
        ->assertForbidden();
});

/**
 * The refresh token only carries ISSUE_ACCESS_TOKEN, so it must not open API routes.
 */
it('does not let a refresh token access the api', function () {
    withToken($this->tokens['refresh_token'])
        ->getJson('/api/v1/customer/orders')
        ->assertForbidden();
});

it('revokes the session tokens on logout', function () {
    expect($this->customer->tokens()->count())->toBe(2);

    withToken($this->tokens['access_token'])
        ->postJson('/api/v1/customer/auth/logout')
        ->assertOk();

    expect($this->customer->fresh()->tokens()->count())->toBe(0);
});

it('rejects the access token after logout', function () {
    withToken($this->tokens['access_token'])->postJson('/api/v1/customer/auth/logout')->assertOk();

    $this->app['auth']->forgetGuards();

    withToken($this->tokens['access_token'])
        ->getJson('/api/v1/customer/orders')
        ->assertUnauthorized();
});

it('issues tokens carrying the expected abilities', function () {
    $accessToken = $this->customer->tokens()->where('name', 'AccessToken')->first();
    $refreshToken = $this->customer->tokens()->where('name', 'RefreshToken')->first();

    expect($accessToken->abilities)->toContain(TokenAbility::ACCESS_API->value)
        ->and($refreshToken->abilities)->toContain(TokenAbility::ISSUE_ACCESS_TOKEN->value);
});

/**
 * Both tokens from one login share a session id, which is what lets logout revoke
 * the pair without touching the user's other devices.
 */
it('pairs the access and refresh tokens by session', function () {
    $sessions = $this->customer->tokens()->pluck('session_id')->unique();

    expect($sessions)->toHaveCount(1);
});
