<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Traits\ApiResponse;

class RateLimiterThrottleMiddleware
{
    use ApiResponse;

    /**
     * Create a new middleware instance.
     *
     * @param RateLimiter $limiter
     * @return void
     */
    public function __construct(protected RateLimiter $limiter){}

    /**
     * Handle an incoming request and apply rate limiting logic.
     *
     * @param Request $request
     * @param Closure $next
     * @return Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        $limiterName = $this->resolveLimiter($request);

        if ($limiterName === null) {
            return $next($request);
        }

        $key = $this->resolveKey($request, $limiterName);
        $maxAttempts = $this->resolveMaxAttempts($limiterName);

        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            return $this->apiResponse([], $this->resolveMessage($limiterName), 429);
        }

        $this->limiter->hit($key, 60);

        return $next($request);
    }

    /**
     * Resolve the appropriate limiter name based on the request.
     *
     * @param Request $request
     * @return string|null
     */
    protected function resolveLimiter(Request $request): ?string
    {
        $routeName = $request->route()?->getName() ?? '';

        if (str_starts_with($routeName, 'admin.') && ! str_starts_with($routeName, 'admin.auth.')) {
            return null;
        }

        if (str_contains($routeName, '.auth.')) {
            return 'auth';
        }

        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return 'forms';
        }

        return 'general';
    }

    /**
     * Generate a unique rate limiting key for the request.
     *
     * @param Request $request
     * @param string $limiterName
     * @return string
     */
    protected function resolveKey(Request $request, string $limiterName): string
    {
        $identifier = $limiterName === 'auth'
            ? $request->ip()
            : ($request->user()?->id ?? $request->ip());

        return $limiterName . ':' . $identifier;
    }

    /**
     * Determine the maximum allowed attempts for a limiter.
     *
     * @param string $limiterName
     * @return int
     */
    protected function resolveMaxAttempts(string $limiterName): int
    {
        return match ($limiterName) {
            'auth'    => 10,
            'forms'   => 20,
            'general' => 60,
            default   => 60,
        };
    }

    /**
     * Resolve the response message for rate limiting.
     *
     * @param string $limiterName
     * @return string
     */
    protected function resolveMessage(string $limiterName): string
    {
        return match ($limiterName) {
            'auth'    => __('limiters.auth'),
            'forms'   => __('limiters.forms'),
            default   => __('limiters.default'),
        };
    }
}