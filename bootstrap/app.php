<?php

use App\Exceptions\Handler;
use App\Http\Middleware\EnsureAdminMiddleware;
use App\Http\Middleware\EnsureRiderMiddleware;
use App\Http\Middleware\EnsureVendorIsVerifiedMiddleware;
use App\Http\Middleware\EnsureVendorMiddleware;
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

            Route::prefix('api/v1/admin')->middleware(['api', 'auth:sanctum', 'isAdmin'])
                ->as('admin.')
                ->group(base_path('routes/api/v1/admin/admin.php'));

            Route::prefix('api/v1/vendor/auth')->middleware(['api'])
                ->as('vendor.auth.')
                ->group(base_path('routes/api/v1/vendor/auth.php'));

            Route::prefix('api/v1/vendor')->middleware(['api', 'auth:sanctum', 'isVendor'])
                ->as('vendor.')
                ->group(base_path('routes/api/v1/vendor/vendor.php'));

            Route::prefix('api/v1/rider/auth')->middleware(['api'])
                ->as('rider.auth.')
                ->group(base_path('routes/api/v1/rider/auth.php'));

            Route::prefix('api/v1/rider')->middleware(['api', 'auth:sanctum', 'isRider'])
                ->as('rider.')
                ->group(base_path('routes/api/v1/rider/rider.php'));

            Route::prefix('api/v1/customer/auth')->middleware(['api'])
                ->as('customer.auth.')
                ->group(base_path('routes/api/v1/customer/auth.php'));
        }
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'isAdmin'         => EnsureAdminMiddleware::class,
            'isVendor'        => EnsureVendorMiddleware::class,
            'vendor.verified' => EnsureVendorIsVerifiedMiddleware::class,
            'isRider'         => EnsureRiderMiddleware::class,
            'ability'         => CheckForAnyAbility::class,
        ]);
    })
    ->withSingletons([
        ExceptionHandler::class => Handler::class,
    ])
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
