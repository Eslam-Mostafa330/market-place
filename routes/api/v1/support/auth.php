<?php

use App\Enums\TokenAbility;
use App\Http\Controllers\Api\V1\Support\AuthController;
use App\Http\Controllers\Api\V1\Support\PasswordResetController;
use App\Http\Controllers\Api\V1\Support\TwoFactorController;
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

# ----- Password Reset Routes
Route::controller(PasswordResetController::class)->prefix('password')->group(function () {
    Route::post('/forgot', 'forgotPassword');
    Route::post('/reset', 'resetPassword');
});