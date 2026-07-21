<?php

use App\Models\TrustedDevice;
use App\Models\TwoFactorCode;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

beforeEach(function () {
    Mail::fake();

    $this->admin = User::factory()->admin()->create([
        'email'    => 'admin@example.com',
        'password' => Hash::make('Str0ng!Passw0rd'),
    ]);

    cache()->flush();
});

function adminLogin(array $overrides = []): Illuminate\Testing\TestResponse
{
    return test()->postJson('/api/v1/admin/auth/login', [
        'email'    => 'admin@example.com',
        'password'   => 'Str0ng!Passw0rd',
        ...$overrides,
    ]);
}

/**
 * Admins are the only role with 2FA enabled, so a correct password alone must not
 * produce API tokens.
 */
it('does not issue tokens before the otp is verified', function () {
    $response = adminLogin()->assertOk();

    expect($response->json('data.temp_token'))->not->toBeNull()
        ->and($response->json('data.access_token'))->toBeNull()
        ->and($this->admin->tokens()->count())->toBe(0);
});

it('stores a hashed otp rather than the plain code', function () {
    adminLogin()->assertOk();

    $record = TwoFactorCode::where('user_id', $this->admin->id)->first();

    expect($record)->not->toBeNull()
        ->and($record->code)->not->toMatch('/^\d{6}$/');
});

it('issues tokens once the correct otp is submitted', function () {
    $tempToken = adminLogin()->json('data.temp_token');

    TwoFactorCode::where('user_id', $this->admin->id)->update(['code' => Hash::make('123456')]);

    $this->app['auth']->forgetGuards();

    $response = $this->postJson('/api/v1/admin/auth/otp/verify', [
        'temp_token' => $tempToken,
        'code'       => '123456',
    ])->assertOk();

    expect($response->json('data.access_token'))->not->toBeNull()
        ->and($response->json('data.refresh_token'))->not->toBeNull();
});

it('rejects a wrong otp', function () {
    $tempToken = adminLogin()->json('data.temp_token');

    TwoFactorCode::where('user_id', $this->admin->id)->update(['code' => Hash::make('123456')]);

    $this->app['auth']->forgetGuards();

    $this->postJson('/api/v1/admin/auth/otp/verify', [
        'temp_token' => $tempToken,
        'code'       => '999999',
    ])->assertStatus(401);

    expect($this->admin->fresh()->tokens()->count())->toBe(0);
});

it('rejects an unknown temp token', function () {
    adminLogin()->assertOk();

    $this->app['auth']->forgetGuards();

    $this->postJson('/api/v1/admin/auth/otp/verify', [
        'temp_token' => Str::random(64),
        'code'       => '123456',
    ])->assertStatus(422);
});

it('rejects an expired otp', function () {
    $tempToken = adminLogin()->json('data.temp_token');

    TwoFactorCode::where('user_id', $this->admin->id)->update([
        'code'       => Hash::make('123456'),
        'expires_at' => now()->subMinute(),
    ]);

    $this->app['auth']->forgetGuards();

    $this->postJson('/api/v1/admin/auth/otp/verify', [
        'temp_token' => $tempToken,
        'code'       => '123456',
    ])->assertStatus(401);
});

it('trusts the device after a successful verification', function () {
    $tempToken = adminLogin()->json('data.temp_token');

    TwoFactorCode::where('user_id', $this->admin->id)->update(['code' => Hash::make('123456')]);

    $this->app['auth']->forgetGuards();

    $this->postJson('/api/v1/admin/auth/otp/verify', [
        'temp_token' => $tempToken,
        'code'       => '123456',
    ])->assertOk();

    expect(TrustedDevice::where('user_id', $this->admin->id)->count())->toBeGreaterThan(0);
});

it('replaces any previous otp when a new one is sent', function () {
    adminLogin()->assertOk();

    $first = TwoFactorCode::where('user_id', $this->admin->id)->first();

    $this->app['auth']->forgetGuards();

    cache()->flush();

    $this->postJson('/api/v1/admin/auth/otp/resend', ['temp_token' => $first->temp_token])->assertOk();

    expect(TwoFactorCode::where('user_id', $this->admin->id)->count())->toBe(1)
        ->and(TwoFactorCode::where('user_id', $this->admin->id)->first()->temp_token)
        ->not->toBe($first->temp_token);
});

/**
 * Customers have 2FA disabled, so their login must complete in a single step.
 */
it('does not require an otp for a customer', function () {
    User::factory()->customer()->create([
        'email'    => 'customer@example.com',
        'password' => Hash::make('Str0ng!Passw0rd'),
    ]);

    $this->app['auth']->forgetGuards();

    $response = $this->postJson('/api/v1/customer/auth/login', [
        'identifier' => 'customer@example.com',
        'password'   => 'Str0ng!Passw0rd',
    ])->assertOk();

    expect($response->json('data.access_token'))->not->toBeNull()
        ->and($response->json('data.temp_token'))->toBeNull();
});
