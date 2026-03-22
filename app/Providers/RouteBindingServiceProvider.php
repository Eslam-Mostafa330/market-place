<?php

namespace App\Providers;

use App\Models\BusinessCategory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class RouteBindingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->bindBusinessCategory();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }

    private function bindBusinessCategory(): void
    {
        Route::bind('businessCategory', function (string $value) {
            return Cache::remember("business_category:slug:{$value}", now()->addDays(90),
                fn () => BusinessCategory::where('slug', $value)->firstOrFail()
            );
        });
    }
}