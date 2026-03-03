<?php

use App\Exceptions\Handler;
use App\Http\Middleware\EnsureAdminMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Http\Middleware\CheckForAnyAbility;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        then: function () {
            Route::prefix('api/v1/admin/auth')->middleware(['api'])
                ->as('admin.auth.')
                ->group(base_path('routes/api/v1/admin/auth.php'));

            Route::prefix('api/v1/admin')->middleware(['api', 'auth:sanctum', 'isAdmin'])
                ->as('admin.')
                ->group(base_path('routes/api/v1/admin/admin.php'));
        }
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'isAdmin' => EnsureAdminMiddleware::class,
            'ability' => CheckForAnyAbility::class,
        ]);
    })
    ->withSingletons([
        ExceptionHandler::class => Handler::class,
    ])
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
