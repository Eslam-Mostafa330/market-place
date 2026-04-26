<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockDirectAccessMiddleware
{
    /**
     * Handle an incoming request.
     * 
     * This middleware restricts direct access to the application by validating
     * the request origin or referer against a predefined list of allowed
     * frontend domains. Requests originating from untrusted sources are blocked.
     *
     * Behavior:
     * - Allows all requests in the local environment for development flexibility.
     * - Allows all OPTIONS requests to ensure proper handling of CORS preflight.
     * - Validates Origin and Referer headers against configured frontend URLs.
     * - Returns a 404 response for unauthorized direct access attempts.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (app()->environment('local')) {
            return $next($request);
        }

        if ($request->isMethod('OPTIONS')) {
            return $next($request);
        }

        $allowedOrigins = array_filter([
            env('FRONTEND_URL'),
            env('FRONTEND_URL_WWW'),
        ]);

        $origin  = $request->header('Origin', '');
        $referer = $request->header('Referer', '');

        $isAllowed = collect($allowedOrigins)->contains(
            fn($domain) => str_starts_with($origin, $domain) || str_starts_with($referer, $domain)
        );

        return $isAllowed ? $next($request) : response('', 404);
    }
}