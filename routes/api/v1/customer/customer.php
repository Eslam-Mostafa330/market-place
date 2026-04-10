<?php

use App\Http\Controllers\Api\V1\Customer\AddressController;
use App\Http\Controllers\Api\V1\Customer\FavoriteController;
use App\Http\Controllers\Api\V1\Customer\NotificationController;
use App\Http\Controllers\Api\V1\Customer\OrderController;
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

# ----- Favorite Routes
Route::controller(FavoriteController::class)->prefix('favorites')->group(function () {
    Route::get('/', 'index');
    Route::delete('/products/{product}', 'destroy');
});

# ----- Order Routes
Route::apiResource('orders', OrderController::class)->except('update', 'destroy');
Route::post('orders/{order}/cancel', [OrderController::class, 'cancel']);

# ----- Notification Routes
Route::controller(NotificationController::class)->prefix('notifications')->group(function () {
    Route::get('/', 'index');
    Route::get('/unread-count', 'unreadNotificationsCount');
    Route::post('/{notification}/read', 'markAsRead');
    Route::post('/read-all', 'markAllAsRead');
});
