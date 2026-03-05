<?php

use App\Enums\TokenAbility;
use App\Http\Controllers\Api\V1\Vendor\AuthController;
use App\Http\Controllers\Api\V1\Vendor\EmailVerificationController;
use App\Http\Controllers\Api\V1\Vendor\PasswordResetController;
use Illuminate\Support\Facades\Route;


# ----- Auth Routes
Route::controller(AuthController::class)->group(function () {
    Route::post('/register', 'register');
    Route::post('/login', 'login');
    Route::post('/refresh-token', 'refreshToken')->middleware(['auth:sanctum', 'ability:'.TokenAbility::ISSUE_ACCESS_TOKEN->value]);
    Route::post('/logout', 'logout')->middleware('auth:sanctum');
});

# ----- Email Verification Routes
Route::controller(EmailVerificationController::class)->group(function () {
    Route::post('/verify-email', 'verify');
    Route::post('/verify-email/resend', 'resend');
});

# ----- Password Reset Routes
Route::controller(PasswordResetController::class)->group(function () {
    Route::post('/forgot-password', 'forgotPassword');
    Route::post('/reset-password', 'resetPassword');
});