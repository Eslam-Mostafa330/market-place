<?php

use App\Enums\TokenAbility;
use App\Http\Controllers\Api\V1\Customer\AuthController;
use App\Http\Controllers\Api\V1\Customer\PasswordResetController;
use App\Http\Controllers\Api\V1\Customer\VerificationController;
use Illuminate\Support\Facades\Route;


# ----- Auth Routes
Route::controller(AuthController::class)->group(function () {
    Route::post('/register', 'register');
    Route::post('/login', 'login');
    Route::post('/refresh', 'refreshToken')->middleware(['auth:sanctum', 'ability:'.TokenAbility::ISSUE_ACCESS_TOKEN->value]);
    Route::post('/logout', 'logout')->middleware('auth:sanctum');
});

# ----- Verification Routes
Route::controller(VerificationController::class)->prefix('email')->group(function () {
    Route::post('/verify', 'verify');
    Route::post('/resend', 'resend');
});

# ----- Password Reset Routes
Route::controller(PasswordResetController::class)->prefix('password')->group(function () {
    Route::post('/forgot', 'forgotPassword');
    Route::post('/reset', 'resetPassword');
});