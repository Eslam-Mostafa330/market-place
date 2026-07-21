<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockDirectAccessMiddleware
{
    /**
     * Server-to-server endpoints that bypass this middleware.
     *
     * These requests authenticate by other means (e.g. Stripe signature).
     */
    private const EXEMPT_PATHS = [
        'api/v1/stripe/webhook',
    ];

    /**
     * Lightweight request filter to reduce unsolicited / automated traffic.
     *
     * This middleware is for reduces noise from
     * direct access attempts and automated scanners.
     *
     * Behavior:
     * - Allows all requests in the local and testing environments
     * - Allows OPTIONS requests (CORS preflight)
     * - Allows requests with Bearer tokens (API / mobile clients)
     * - Allows browser requests from trusted frontend domains
     * - Returns 404 for all other requests
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment(['local', 'testing'])) {
            return $next($request);
        }

        if ($request->is(self::EXEMPT_PATHS)) {
            return $next($request);
        }

        if ($request->isMethod('OPTIONS')) {
            return $next($request);
        }

        if ($request->bearerToken()) {
            return $next($request);
        }

        $allowedOrigins = array_filter([
            env('FRONTEND_URL'),
            env('FRONTEND_URL_WWW'),
        ]);

        $origin  = $request->header('Origin');
        $referer = $request->header('Referer');

        $originHost = $origin
            ? (parse_url($origin, PHP_URL_HOST) ?: null)
            : null;

        $refererHost = $referer
            ? (parse_url($referer, PHP_URL_HOST) ?: null)
            : null;

        $allowedHosts = collect($allowedOrigins)
            ->map(fn ($url) => parse_url($url, PHP_URL_HOST) ?: null)
            ->filter();

        $isFromFrontend = $allowedHosts->contains($originHost)
            || $allowedHosts->contains($refererHost);

        return $isFromFrontend ? $next($request) : response('', 404);
    }
}