<?php

use App\Http\Controllers\Api\V1\Rider\DashboardController;
use App\Http\Controllers\Api\V1\Rider\NotificationController;
use App\Http\Controllers\Api\V1\Rider\OrderController;
use App\Http\Controllers\Api\V1\Rider\PayoutController;
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
    Route::patch('/availability', 'updateAvailability');
    Route::patch('/location', 'updateLocation');
});

# ----- Notification Routes
Route::controller(NotificationController::class)->prefix('notifications')->group(function () {
    Route::get('/', 'index');
    Route::get('/unread-count', 'unreadNotificationsCount');
    Route::post('/{notification}/read', 'markAsRead');
    Route::post('/read-all', 'markAllAsRead');
});

# ----- Order Routes
Route::controller(OrderController::class)->prefix('orders')->group(function () {
    Route::post('/{orderId}/reject', 'reject');
    Route::post('/{orderId}/pickup', 'pickup');
    Route::post('/{orderId}/deliver', 'deliver');
});

# ----- Payout Routes
Route::controller(PayoutController::class)->prefix('payouts')->group(function () {
    Route::get('/', 'index');
    Route::get('/{payout}', 'show');
});

# ----- Dashboard Routes
Route::get('dashboard', DashboardController::class);