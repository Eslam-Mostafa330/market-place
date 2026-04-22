<?php

use App\Http\Controllers\Api\V1\Admin\AdminController;
use App\Http\Controllers\Api\V1\Admin\AvailableRiderOrderController;
use App\Http\Controllers\Api\V1\Admin\BusinessCategoryController;
use App\Http\Controllers\Api\V1\Admin\CustomerController;
use App\Http\Controllers\Api\V1\Admin\DashboardController;
use App\Http\Controllers\Api\V1\Admin\NotificationController;
use App\Http\Controllers\Api\V1\Admin\OrderController;
use App\Http\Controllers\Api\V1\Admin\ProfileController;
use App\Http\Controllers\Api\V1\Admin\ReviewController;
use App\Http\Controllers\Api\V1\Admin\RiderController;
use App\Http\Controllers\Api\V1\Admin\SettingController;
use App\Http\Controllers\Api\V1\Admin\StoreBranchController;
use App\Http\Controllers\Api\V1\Admin\StoreController;
use App\Http\Controllers\Api\V1\Admin\StoreProductCategoryController;
use App\Http\Controllers\Api\V1\Admin\StoreProductController;
use App\Http\Controllers\Api\V1\Admin\VendorBusinessProfileController;
use App\Http\Controllers\Api\V1\Admin\VendorController;
use App\Http\Controllers\Api\V1\Admin\VendorPayoutController;
use App\Http\Controllers\Api\V1\Admin\RiderPayoutController;
use Illuminate\Support\Facades\Route;


# ----- Profile Routes
Route::controller(ProfileController::class)->prefix('profile')->group(function () {
    Route::get('/', 'show');
    Route::get('/summary', 'showProfileSummary');
    Route::patch('/update', 'update');
});

# ----- Admin Routes
Route::apiResource('admins', AdminController::class)->except('show');
Route::patch('admins/{admin}/toggle-status', [AdminController::class, 'toggleStatus']);

# ----- Vendor Routes
Route::apiResource('vendors', VendorController::class)->except('show');
Route::patch('vendors/{vendor}/toggle-status', [VendorController::class, 'toggleStatus']);

# ----- Vendor Business Profile Routes
Route::get('vendors/{vendor}/business-profile', [VendorBusinessProfileController::class, 'show']);
Route::patch('vendors/{vendor}/business-profile', [VendorBusinessProfileController::class, 'update']);

# ----- Customer Routes
Route::apiResource('customers', CustomerController::class)->except('show');
Route::patch('customers/{customer}/toggle-status', [CustomerController::class, 'toggleStatus']);

# ----- Rider Routes
# Custom routes that could match a resource parameter (e.g. /riders/available) must come before apiResource.
Route::get('riders/available', AvailableRiderOrderController::class);
Route::apiResource('riders', RiderController::class);
Route::patch('riders/{rider}/toggle-status', [RiderController::class, 'toggleStatus']);

# ----- Business Category Routes
Route::apiResource('business-categories', BusinessCategoryController::class)->except('show');

# ----- Store Product Category Routes
Route::apiResource('product-categories', StoreProductCategoryController::class)->except('show');

# ----- Store Routes
Route::apiResource('stores', StoreController::class)->only('index', 'destroy');
Route::patch('stores/{store}/commission', [StoreController::class, 'updateCommission']);

# ----- Store Branches Routes
Route::apiResource('stores.branches', StoreBranchController::class)->except('update', 'store')->scoped();

# ----- Store Product Routes
Route::apiResource('stores.products', StoreProductController::class)->except('update', 'store')->scoped();

# ----- Review Routes
Route::apiResource('reviews', ReviewController::class)->only('index', 'destroy');

# ----- Notification Routes
Route::controller(NotificationController::class)->prefix('notifications')->group(function () {
    Route::get('/', 'index');
    Route::get('/unread-count', 'unreadNotificationsCount');
    Route::post('/{notification}/read', 'markAsRead');
    Route::post('/read-all', 'markAllAsRead');
});

# ----- Order Routes
Route::controller(OrderController::class)->prefix('orders')->group(function () {
    Route::get('/', 'index');
    Route::get('/{order}', 'show');
    Route::post('/{orderId}/assign-rider', 'assignRider');
    Route::post('/{orderId}/cancel', 'cancel');
    Route::post('/{orderId}/extend-search', 'extendSearch');
});

# ----- Rider Payout Routes
Route::controller(RiderPayoutController::class)->prefix('rider-payouts')->group(function () {
    Route::get('/', 'index');
    Route::get('/{riderPayout}', 'show');
    Route::post('/{riderPayout}/complete', 'complete');
    Route::patch('/{riderPayout}/details', 'update');
});

# ----- Vendor Payout Routes
Route::controller(VendorPayoutController::class)->prefix('vendor-payouts')->group(function () {
    Route::get('/', 'index');
    Route::get('/{vendorPayout}', 'show');
    Route::post('/{vendorPayout}/complete', 'complete');
    Route::patch('/{vendorPayout}/details', 'update');
});

# ----- Setting Routes
Route::controller(SettingController::class)->prefix('settings')->group(function () {
    Route::get('/loyalty-points', 'showLoyaltyPoints');
    Route::patch('/loyalty-points', 'updateLoyaltyPoints');
    Route::get('/contact', 'showContact');
    Route::patch('/contact', 'updateContact');
    Route::get('/social', 'showSocial');
    Route::patch('/social', 'updateSocial');
});

# ----- Dashboard Routes
Route::get('dashboard', DashboardController::class);