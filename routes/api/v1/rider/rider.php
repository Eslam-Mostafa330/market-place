<?php

use App\Http\Controllers\Api\V1\Rider\ProfileController;
use App\Http\Controllers\Api\V1\Rider\RiderLocationController;
use Illuminate\Support\Facades\Route;


# ----- Profile Routes
Route::controller(ProfileController::class)->prefix('profile')->group(function () {
    Route::get('/', 'show');
    Route::get('/summary', 'showProfileSummary');
    Route::patch('/', 'update');
});

Route::controller(RiderLocationController::class)->group(function () {
    Route::get('/availability', 'getAvailability');
    Route::patch('/availability/update', 'updateAvailability');
    Route::patch('/location/update', 'updateLocation');
});
