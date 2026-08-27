<?php

use App\Enums\TokenAbility;
use App\Exceptions\Handler;
use App\Http\Middleware\BlockDirectAccessMiddleware;
use App\Http\Middleware\EnsureAdminMiddleware;
use App\Http\Middleware\EnsureCustomerMiddleware;
use App\Http\Middleware\EnsureRiderMiddleware;
use App\Http\Middleware\EnsureSupportMiddleware;
use App\Http\Middleware\EnsureVendorIsVerifiedMiddleware;
use App\Http\Middleware\EnsureVendorMiddleware;
use App\Http\Middleware\RateLimiterThrottleMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        then: function () {
            Route::prefix('api/v1/admin/auth')->middleware(['api'])
                ->as('admin.auth.')
                ->group(base_path('routes/api/v1/admin/auth.php'));

            Route::prefix('api/v1/admin')->middleware(['api', 'auth:sanctum', 'ability:'.TokenAbility::ACCESS_API->value, 'isAdmin'])
                ->as('admin.')
                ->group(base_path('routes/api/v1/admin/admin.php'));

            Route::prefix('api/v1/vendor/auth')->middleware(['api'])
                ->as('vendor.auth.')
                ->group(base_path('routes/api/v1/vendor/auth.php'));

            Route::prefix('api/v1/vendor')->middleware(['api', 'auth:sanctum', 'ability:'.TokenAbility::ACCESS_API->value, 'isVendor'])
                ->as('vendor.')
                ->group(base_path('routes/api/v1/vendor/vendor.php'));

            Route::prefix('api/v1/rider/auth')->middleware(['api'])
                ->as('rider.auth.')
                ->group(base_path('routes/api/v1/rider/auth.php'));

            Route::prefix('api/v1/rider')->middleware(['api', 'auth:sanctum', 'ability:'.TokenAbility::ACCESS_API->value, 'isRider'])
                ->as('rider.')
                ->group(base_path('routes/api/v1/rider/rider.php'));

            Route::prefix('api/v1/support/auth')->middleware(['api'])
                ->as('support.auth.')
                ->group(base_path('routes/api/v1/support/auth.php'));

            Route::prefix('api/v1/support')->middleware(['api', 'auth:sanctum', 'ability:'.TokenAbility::ACCESS_API->value, 'isSupport'])
                ->as('support.')
                ->group(base_path('routes/api/v1/support/support.php'));

            Route::prefix('api/v1/customer/auth')->middleware(['api'])
                ->as('customer.auth.')
                ->group(base_path('routes/api/v1/customer/auth.php'));

            Route::prefix('api/v1/customer')->middleware(['api', 'auth:sanctum', 'ability:'.TokenAbility::ACCESS_API->value, 'isCustomer'])
                ->as('customer.')
                ->group(base_path('routes/api/v1/customer/customer.php'));

            Route::prefix('api/v1')->middleware(['api'])
                ->as('public.')
                ->group(base_path('routes/api/v1/public.php'));
        }
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->statefulApi();
        $middleware->append(BlockDirectAccessMiddleware::class);
        $middleware->appendToGroup('api', [RateLimiterThrottleMiddleware::class]);

        $middleware->alias([
            'isAdmin'         => EnsureAdminMiddleware::class,
            'isVendor'        => EnsureVendorMiddleware::class,
            'vendor.verified' => EnsureVendorIsVerifiedMiddleware::class,
            'isRider'         => EnsureRiderMiddleware::class,
            'isCustomer'      => EnsureCustomerMiddleware::class,
            'isSupport'       => EnsureSupportMiddleware::class,
            'ability'         => CheckForAnyAbility::class,
        ]);
    })
    ->withSingletons([
        ExceptionHandler::class => Handler::class,
    ])
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
