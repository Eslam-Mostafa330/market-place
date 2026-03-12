<?php

use App\Enums\TokenAbility;
use App\Http\Controllers\Api\V1\Admin\AuthController;
use App\Http\Controllers\Api\V1\Admin\TwoFactorController;
use Illuminate\Support\Facades\Route;

# ----- Auth Routes
Route::controller(AuthController::class)->group(function () {
    Route::post('/login', 'login');
    Route::post('/refresh', 'refreshToken')->middleware(['auth:sanctum', 'ability:'.TokenAbility::ISSUE_ACCESS_TOKEN->value]);
    Route::post('/logout', 'logout')->middleware('auth:sanctum');
});

# ----- Two-Factor Routes
Route::controller(TwoFactorController::class)->prefix('otp')->group(function () {
    Route::post('/verify', 'verifyOtp');
    Route::post('/resend', 'resendOtp');
});