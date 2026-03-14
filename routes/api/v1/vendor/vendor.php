<?php

use App\Http\Controllers\Api\V1\Vendor\BusinessProfileController;
use App\Http\Controllers\Api\V1\Vendor\ProfileController;
use App\Http\Controllers\Api\V1\Vendor\StoreController;
use Illuminate\Support\Facades\Route;


# ----- Profile Routes
Route::controller(ProfileController::class)->prefix('profile')->group(function () {
    Route::get('/', 'show');
    Route::get('/summary', 'showProfileSummary');
    Route::patch('/', 'update');
});

# ----- Business Profile Routes
Route::controller(BusinessProfileController::class)->prefix('business-profile')->group(function () {
    Route::get('/', 'show');
    Route::patch('/', 'update');
});

# ----- Store Routes
Route::apiResource('stores', StoreController::class)->middleware('vendor.verified');