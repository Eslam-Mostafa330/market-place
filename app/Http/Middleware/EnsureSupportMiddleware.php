<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSupportMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Admins are admitted alongside agents so they can supervise the desk
     * without a second set of endpoints.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_if(! $user || ! $user->staffsSupportDesk(), 404);

        return $next($request);
    }
}
