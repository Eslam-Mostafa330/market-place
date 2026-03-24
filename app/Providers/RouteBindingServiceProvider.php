<?php

namespace App\Providers;

use App\Models\BusinessCategory;
use App\Models\Store;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class RouteBindingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->bindStore();
        $this->bindBusinessCategory();
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Bind {store} route parameter.
     * The UUID resolve by primary key
     * The slug resolve by slug with cache (public routes)
     */
    private function bindStore(): void
    {
        Route::bind('store', function (string $value) {
            if (Str::isUuid($value)) {
                return Store::findOrFail($value);
            }

            return Cache::remember("store:slug:{$value}", now()->addDays(90),
                fn () => Store::where('slug', $value)->firstOrFail()
            );
        });
    }

    /**
     * Bind {businessCategory} route parameter.
     * The UUID resolve by primary key
     * The slug resolve by slug with cache (public routes)
     */
    private function bindBusinessCategory(): void
    {
        Route::bind('businessCategory', function (string $value) {
            if (Str::isUuid($value)) {
                return BusinessCategory::findOrFail($value);
            }

            return Cache::remember("business_category:slug:{$value}", now()->addDays(120),
                fn () => BusinessCategory::where('slug', $value)->firstOrFail()
            );
        });
    }
}