<?php

use App\Http\Middleware\BlockDirectAccessMiddleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * BlockDirectAccessMiddleware waves through the local and testing environments, so
 * an ordinary feature test can never observe what it does in production. These
 * tests drive the middleware directly with the environment forced, which is the
 * only way to cover the filter's real behaviour.
 */
function passThroughFilter(string $path, array $headers = []): Response
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
});

/**
 * Stripe sends no bearer token, no Origin and no Referer. Before the exemption the
 * filter answered every webhook with a 404 in production, so Stripe retried for
 * days and orders were never marked paid — a failure that cannot reproduce locally.
 */
it('lets the stripe webhook through in production', function () {
    expect(passThroughFilter('/api/v1/stripe/webhook')->getStatusCode())->toBe(200);
});

/**
 * The exemption must be narrow: everything else still has to satisfy the filter,
 * otherwise the fix has quietly disabled it.
 */
it('still blocks an ordinary path with no token or origin', function () {
    expect(passThroughFilter('/api/v1/customer/orders')->getStatusCode())->toBe(404);
});

it('still allows a request carrying a bearer token', function () {
    $response = passThroughFilter('/api/v1/customer/orders', [
        'Authorization' => 'Bearer some-token',
    ]);

    expect($response->getStatusCode())->toBe(200);
});

/**
 * The exemption is matched on the path, so a lookalike must not inherit it.
 */
it('does not exempt a path that merely resembles the webhook', function () {
    expect(passThroughFilter('/api/v1/stripe/webhook/extra')->getStatusCode())->toBe(404);
    expect(passThroughFilter('/api/v1/stripe')->getStatusCode())->toBe(404);
});
