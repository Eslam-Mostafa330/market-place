<?php

namespace App\Providers;

use App\Models\BusinessCategory;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\Sanctum;
use App\Models\PersonalAccessToken;
use App\Models\User;
use App\Models\UserAddress;
use App\Observers\BusinessCategoryObserver;
use App\Observers\UserAddressObserver;
use App\Observers\UserObserver;
use Illuminate\Database\Eloquent\Model;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        if ($this->app->environment('local') && class_exists(\Laravel\Telescope\TelescopeServiceProvider::class)) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(TelescopeServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
        Model::preventLazyLoading(! app()->isProduction()); // Prevent lazy loading in dev mode
        User::observe(UserObserver::class);
        UserAddress::observe(UserAddressObserver::class);
        BusinessCategory::observe(BusinessCategoryObserver::class);
    }
}