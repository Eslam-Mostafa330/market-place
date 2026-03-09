<?php

use App\Http\Controllers\Api\V1\Admin\AdminController;
use App\Http\Controllers\Api\V1\Admin\BusinessCategoryController;
use App\Http\Controllers\Api\V1\Admin\CustomerController;
use App\Http\Controllers\Api\V1\Admin\ProductCategoryController;
use App\Http\Controllers\Api\V1\Admin\ProfileController;
use App\Http\Controllers\Api\V1\Admin\RiderController;
use App\Http\Controllers\Api\V1\Admin\VendorController;
use Illuminate\Support\Facades\Route;


# ----- Profile Routes
Route::controller(ProfileController::class)->prefix('profile')->group(function () {
    Route::get('/', 'showProfile');
    Route::get('/summary', 'profileSummary');
    Route::post('/update', 'updateProfile');
});

# ----- Admin CRUD Routes
Route::apiResource('admins', AdminController::class)->except('show');
Route::patch('admins/{admin}/toggle-status', [AdminController::class, 'toggleStatus']);

# ----- Vendor CRUD Routes
Route::apiResource('vendors', VendorController::class)->except('show');
Route::patch('vendors/{vendor}/toggle-status', [VendorController::class, 'toggleStatus']);

# ----- Customer CRUD Routes
Route::apiResource('customers', CustomerController::class)->except('show');
Route::patch('customers/{customer}/toggle-status', [CustomerController::class, 'toggleStatus']);

# ----- Rider CRUD Routes
Route::apiResource('riders', RiderController::class)->except('show');
Route::patch('riders/{rider}/toggle-status', [RiderController::class, 'toggleStatus']);

# ----- Business Category Routes
Route::apiResource('business-categories', BusinessCategoryController::class)->except('show');

# ----- Product Category Routes
Route::apiResource('product-categories', ProductCategoryController::class)->except('show');
