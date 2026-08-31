<?php

namespace App\Http\Middleware;

use App\Services\Support\SupportPresenceService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RefreshSupportPresence
{
    public function __construct(private readonly SupportPresenceService $presence) {}

    /**
     * Presence is updated in terminate() after the request completes.
     */
    public function handle(Request $request, Closure $next): Response
    {
        return $next($request);
    }

    /**
     * Keep the agent's presence active while using the support desk.
     */
    public function terminate(Request $request, Response $response): void
    {
        $user = $request->user();

        if ($user?->staffsSupportDesk()) {
            $this->presence->touch($user);
        }
    }
}
