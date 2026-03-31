<?php

use App\Http\Controllers\Api\V1\Rider\NotificationController;
use App\Http\Controllers\Api\V1\Rider\ProfileController;
use App\Http\Controllers\Api\V1\Rider\RiderLocationController;
use Illuminate\Support\Facades\Route;


# ----- Profile Routes
Route::controller(ProfileController::class)->prefix('profile')->group(function () {
    Route::get('/', 'show');
    Route::get('/summary', 'showProfileSummary');
    Route::patch('/', 'update');
});

# ----- Location Routes
Route::controller(RiderLocationController::class)->group(function () {
    Route::get('/availability', 'getAvailability');
    Route::patch('/availability/update', 'updateAvailability');
    Route::patch('/location/update', 'updateLocation');
});

# ----- Notification Routes
Route::controller(NotificationController::class)->prefix('notifications')->group(function () {
    Route::get('/', 'index');
    Route::get('/unread-count', 'unreadNotificationsCount');
    Route::patch('/{notification}/read', 'markAsRead');
    Route::patch('/read-all', 'markAllAsRead');
});
