<?php

use App\Enums\TokenAbility;
use App\Http\Controllers\Api\V1\Admin\AuthController;
use Illuminate\Support\Facades\Route;

Route::controller(AuthController::class)->group(function () {
    Route::post('/login', 'login');
    Route::post('/refresh-token', 'refreshToken')->middleware(['auth:sanctum', 'ability:'.TokenAbility::ISSUE_ACCESS_TOKEN->value]);
    Route::post('/logout', 'logout')->middleware('auth:sanctum');
});