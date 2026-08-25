<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Filters unsolicited traffic: a browser is admitted only from a first-party
 * origin, anything else only with a token or on its way to one.
 */
class BlockDirectAccessMiddleware
{
    /**
     * Bypass the filter — these authenticate by other means (Stripe signature).
     */
    private const EXEMPT_PATHS = [
        'api/v1/stripe/webhook',
    ];

    /**
     * Reachable without a token: a client logging in or resetting a password
     * sends no origin and holds no token yet. Still rate limited per IP.
     */
    private const CREDENTIAL_PATHS = [
        'api/v1/*/auth/*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment(['local', 'testing'])) {
            return $next($request);
        }

        if ($request->is(self::EXEMPT_PATHS) || $request->isMethod('OPTIONS')) {
            return $next($request);
        }

        if ($origin = $this->browserOrigin($request)) {
            return $this->isFirstParty($origin) ? $next($request) : response('', 404);
        }

        return $request->bearerToken() || $request->is(self::CREDENTIAL_PATHS)
            ? $next($request)
            : response('', 404);
    }

    /**
     * The origin of the page issuing the request, or null for a non-browser client.
     */
    private function browserOrigin(Request $request): ?string
    {
        return $request->header('Origin') ?: $request->header('Referer') ?: null;
    }

    /**
     * Determines if the given origin belongs to one of our own frontends.
     */
    private function isFirstParty(string $origin): bool
    {
        $origin  = $this->toOrigin($origin);
        $allowed = array_map($this->toOrigin(...), config('cors.allowed_origins', []));

        return $origin !== null && in_array($origin, $allowed, true);
    }

    /**
     * Convert a URL to its origin, or null if it cannot be parsed.
     */
    private function toOrigin(?string $url): ?string
    {
        $parts = parse_url((string) $url);

        if (empty($parts['scheme']) || empty($parts['host'])) {
            return null;
        }

        $port = isset($parts['port']) ? ':' . $parts['port'] : '';

        return strtolower($parts['scheme'] . '://' . $parts['host']) . $port;
    }
}
