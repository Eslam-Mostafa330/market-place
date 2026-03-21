<?php

use App\Http\Controllers\Api\V1\Customer\AddressController;
use App\Http\Controllers\Api\V1\Customer\ProfileController;
use Illuminate\Support\Facades\Route;


# ----- Profile Routes
Route::controller(ProfileController::class)->prefix('profile')->group(function () {
    Route::get('/', 'show');
    Route::get('/summary', 'showProfileSummary');
    Route::patch('/', 'update');
});

# ----- Address Routes
Route::apiResource('addresses', AddressController::class);
Route::patch('addresses/{address}/default', [AddressController::class, 'setDefault']);