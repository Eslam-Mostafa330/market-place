<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

const SPA_ORIGIN = 'http://localhost:5173';

beforeEach(function () {
    Mail::fake();

    config(['sanctum.stateful' => ['localhost:5173']]);

    $this->customer = User::factory()->customer()->create([
        'email'    => 'customer@example.com',
        'password' => Hash::make('Str0ng!Passw0rd'),
    ]);
});

/**
 * Return type is left off deliberately: Pest runs each file in a generated
 * subclass of the test case, so the concrete type is not nameable here.
 */
function loginAsCustomer(array $headers = [])
{
    return test()->withHeaders($headers)->postJson('/api/v1/customer/auth/login', [
        'identifier' => 'customer@example.com',
        'password'   => 'Str0ng!Passw0rd',
    ]);
}

it('starts a cookie session for the spa and issues no tokens', function () {
    $response = loginAsCustomer(['Origin' => SPA_ORIGIN])->assertOk();

    expect($response->json('data.user'))->not->toBeNull()
        ->and($response->json('data.access_token'))->toBeNull()
        ->and($response->json('data.refresh_token'))->toBeNull()
        ->and($this->customer->tokens()->count())->toBe(0);

    $sessionCookie = collect($response->headers->getCookies())
        ->firstWhere(fn ($cookie) => $cookie->getName() === config('session.cookie'));

    expect($sessionCookie)->not->toBeNull()
        ->and($sessionCookie->isHttpOnly())->toBeTrue();
});

it('issues tokens when no first-party origin is present', function () {
    $response = loginAsCustomer()->assertOk();

    expect($response->json('data.access_token'))->not->toBeNull()
        ->and($response->json('data.refresh_token'))->not->toBeNull()
        ->and($this->customer->tokens()->count())->toBe(2);
});

/**
 * Turning a foreign browser away is BlockDirectAccessMiddleware's job, which it
 * does with a 404 in production before the controller is reached.
 */
it('issues tokens for an origin that is not a stateful domain', function () {
    loginAsCustomer(['Origin' => 'https://someone-elses-site.test'])->assertOk();

    expect($this->customer->tokens()->count())->toBe(2);
});

/**
 * Which branch logout and refresh take follows how the request authenticated,
 * not the Origin header, so a bearer token from the SPA's own origin still
 * takes the token path.
 */
it('revokes tokens on logout even when the spa origin is present', function () {
    $tokens = loginAsCustomer()->json('data');
    $this->app['auth']->forgetGuards();

    $this->withHeaders([
        'Origin'        => SPA_ORIGIN,
        'Authorization' => 'Bearer ' . $tokens['access_token'],
    ])->postJson('/api/v1/customer/auth/logout')->assertOk();

    expect($this->customer->fresh()->tokens()->count())->toBe(0);
});

it('still refreshes a bearer token sent from the spa origin', function () {
    $tokens = loginAsCustomer()->json('data');
    $this->app['auth']->forgetGuards();

    $response = $this->withHeaders([
        'Origin'        => SPA_ORIGIN,
        'Authorization' => 'Bearer ' . $tokens['refresh_token'],
    ])->postJson('/api/v1/customer/auth/refresh')->assertOk();

    expect($response->json('data.access_token'))->not->toBeNull();
});

it('leaves exactly one token pair behind after a refresh', function () {
    $tokens = loginAsCustomer()->json('data');
    $this->app['auth']->forgetGuards();

    $this->withHeader('Authorization', 'Bearer ' . $tokens['refresh_token'])
        ->postJson('/api/v1/customer/auth/refresh')
        ->assertOk();

    expect($this->customer->tokens()->count())->toBe(2)
        ->and($this->customer->tokens()->pluck('session_id')->unique())->toHaveCount(1);
});

/**
 * The caller's own refresh token has to survive, or the client is stranded the
 * moment its access token expires.
 */
it('keeps the current refresh token usable after a password change', function () {
    $tokens = loginAsCustomer()->json('data');
    $this->app['auth']->forgetGuards();

    $this->withHeader('Authorization', 'Bearer ' . $tokens['access_token'])
        ->patchJson('/api/v1/customer/profile', [
            'password'              => 'An0ther!Passw0rd',
            'password_confirmation' => 'An0ther!Passw0rd',
            'current_password'      => 'Str0ng!Passw0rd',
        ])->assertOk();

    $this->app['auth']->forgetGuards();

    $this->withHeader('Authorization', 'Bearer ' . $tokens['refresh_token'])
        ->postJson('/api/v1/customer/auth/refresh')
        ->assertOk();
});

it('revokes the other devices tokens on a password change', function () {
    loginAsCustomer();
    $this->app['auth']->forgetGuards();

    $current = loginAsCustomer()->json('data');
    $this->app['auth']->forgetGuards();

    expect($this->customer->tokens()->count())->toBe(4);

    $this->withHeader('Authorization', 'Bearer ' . $current['access_token'])
        ->patchJson('/api/v1/customer/profile', [
            'password'              => 'An0ther!Passw0rd',
            'password_confirmation' => 'An0ther!Passw0rd',
            'current_password'      => 'Str0ng!Passw0rd',
        ])->assertOk();

    expect($this->customer->tokens()->count())->toBe(2);
});

/**
 * SessionGuard::logoutOtherDevices() wants the current password, so passing it
 * the new one failed every session password change with a 400.
 */
it('changes the password on the session path without erroring', function () {
    $this->actingAs($this->customer);

    $this->withSession([])
        ->withHeader('Origin', SPA_ORIGIN)
        ->patchJson('/api/v1/customer/profile', [
            'password'              => 'An0ther!Passw0rd',
            'password_confirmation' => 'An0ther!Passw0rd',
            'current_password'      => 'Str0ng!Passw0rd',
        ])
        ->assertOk();

    expect(Hash::check('An0ther!Passw0rd', $this->customer->fresh()->password))->toBeTrue();
});

it('drops other session rows on a session password change', function () {
    config(['session.driver' => 'database']);

    $this->actingAs($this->customer);

    DB::table('sessions')->insert([
        'id'            => 'other-device-session',
        'user_id'       => $this->customer->id,
        'ip_address'    => '127.0.0.1',
        'user_agent'    => 'other device',
        'payload'       => '',
        'last_activity' => now()->timestamp,
    ]);

    $this->withSession([])
        ->withHeader('Origin', SPA_ORIGIN)
        ->patchJson('/api/v1/customer/profile', [
            'password'              => 'An0ther!Passw0rd',
            'password_confirmation' => 'An0ther!Passw0rd',
            'current_password'      => 'Str0ng!Passw0rd',
        ])
        ->assertOk();

    expect(DB::table('sessions')->where('id', 'other-device-session')->exists())->toBeFalse();
});
