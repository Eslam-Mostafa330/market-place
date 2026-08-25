<?php

use App\Http\Middleware\BlockDirectAccessMiddleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * The filter waves through local and testing, so an ordinary feature test can
 * never observe it. These drive the middleware directly with the environment
 * forced, which is the only way to cover its real behaviour.
 */
function filterAuth(string $path = '/api/v1/vendor/auth/login', array $headers = []): Response
{
    $request = Request::create($path, 'POST');

    foreach ($headers as $name => $value) {
        $request->headers->set($name, $value);
    }

    return app(BlockDirectAccessMiddleware::class)
        ->handle($request, fn () => new Response('reached', 200));
}

beforeEach(function () {
    app()->detectEnvironment(fn () => 'production');

    config(['cors.allowed_origins' => ['http://localhost:5173']]);
});

it('blocks a login from a foreign browser origin', function () {
    expect(filterAuth('/api/v1/vendor/auth/login', ['Origin' => 'https://evil.test'])->getStatusCode())->toBe(404);
});

it('blocks a login from a foreign browser referer', function () {
    expect(filterAuth('/api/v1/vendor/auth/login', ['Referer' => 'https://evil.test/login'])->getStatusCode())->toBe(404);
});

it('blocks a foreign browser even when it carries a bearer token', function () {
    expect(filterAuth('/api/v1/customer/orders', [
        'Origin'        => 'https://evil.test',
        'Authorization' => 'Bearer stolen-token',
    ])->getStatusCode())->toBe(404);
});

it('lets the first-party frontend through', function () {
    expect(filterAuth('/api/v1/vendor/auth/login', ['Origin' => 'http://localhost:5173'])->getStatusCode())->toBe(200);
});

it('lets a first-party referer through, path and all', function () {
    expect(filterAuth('/api/v1/vendor/auth/login', ['Referer' => 'http://localhost:5173/login'])->getStatusCode())->toBe(200);
});

/**
 * Origins are matched whole, as the CORS layer matches them. A bare host would
 * accept a different scheme or port on the same domain.
 */
it('blocks the allowed host on another scheme or port', function (string $origin) {
    expect(filterAuth('/api/v1/vendor/auth/login', ['Origin' => $origin])->getStatusCode())->toBe(404);
})->with([
    'https://localhost:5173',
    'http://localhost:9999',
    'http://localhost',
]);

/**
 * Sandboxed frames and file:// pages send a literal "null" origin, which has no
 * host to compare and must never be mistaken for a missing header.
 */
it('blocks a null origin', function () {
    expect(filterAuth('/api/v1/vendor/auth/login', ['Origin' => 'null'])->getStatusCode())->toBe(404);
});

it('matches the origin regardless of casing or a trailing slash in config', function () {
    config(['cors.allowed_origins' => ['HTTP://LocalHost:5173/']]);

    expect(filterAuth('/api/v1/vendor/auth/login', ['Origin' => 'http://localhost:5173'])->getStatusCode())->toBe(200);
});

/**
 * A mobile client on its way to a token sends no Origin and holds no bearer
 * token yet. Every one of these answered 404 in production before the
 * CREDENTIAL_PATHS exemption, leaving the token half of the API unreachable.
 */
it('lets a tokenless mobile client reach the auth endpoints', function (string $path) {
    expect(filterAuth($path)->getStatusCode())->toBe(200);
})->with([
    '/api/v1/vendor/auth/login',
    '/api/v1/vendor/auth/register',
    '/api/v1/customer/auth/login',
    '/api/v1/customer/auth/password/forgot',
    '/api/v1/customer/auth/password/reset',
    '/api/v1/customer/auth/email/verify',
    '/api/v1/admin/auth/login',
    '/api/v1/admin/auth/otp/verify',
    '/api/v1/rider/auth/login',
]);

it('still blocks an ordinary path with no token or origin', function () {
    expect(filterAuth('/api/v1/customer/orders')->getStatusCode())->toBe(404);
});

it('still allows an ordinary path with a bearer token', function () {
    expect(filterAuth('/api/v1/customer/orders', [
        'Authorization' => 'Bearer some-token',
    ])->getStatusCode())->toBe(200);
});
