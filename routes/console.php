<?php

use App\Models\CustomerProfile;
use App\Services\CustomerPreferencesService;
use Illuminate\Support\Facades\Schedule;


Schedule::command('tokens:delete-expired')->dailyAt('02:00');
Schedule::command('two-factor:delete-expired')->dailyAt('01:00');
Schedule::command('app:mark-stale-riders-unavailable')->everyTenMinutes();

Schedule::call(function () {
    CustomerProfile::chunk(100, function ($profiles) {
        foreach ($profiles as $profile) {
            app(CustomerPreferencesService::class)->rebuildAll($profile);
        }
    });
})->dailyAt('03:00');