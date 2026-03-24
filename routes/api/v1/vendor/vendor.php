<?php

use App\Http\Controllers\Api\V1\Vendor\BusinessCategoryController;
use App\Http\Controllers\Api\V1\Vendor\BusinessProfileController;
use App\Http\Controllers\Api\V1\Vendor\ProfileController;
use App\Http\Controllers\Api\V1\Vendor\StoreBranchController;
use App\Http\Controllers\Api\V1\Vendor\StoreController;
use App\Http\Controllers\Api\V1\Vendor\StoreProductCategoryController;
use App\Http\Controllers\Api\V1\Vendor\StoreProductController;
use Illuminate\Support\Facades\Route;


# ----- Profile Routes
Route::controller(ProfileController::class)->prefix('profile')->group(function () {
    Route::get('/', 'show');
    Route::get('/summary', 'showProfileSummary');
    Route::patch('/', 'update');
});

# ----- Business Profile Routes
Route::controller(BusinessProfileController::class)->prefix('business-profile')->group(function () {
    Route::get('/', 'show');
    Route::patch('/', 'update');
});

# ----- Business Category Routes
Route::get('business-categories', BusinessCategoryController::class);

# ----- Routes of the vendor that should be verified ----- #
Route::middleware('vendor.verified')->group(function () {
    # ----- Store Routes
    Route::apiResource('stores', StoreController::class)->except('show');

    # ----- Store Branch Routes
    Route::apiResource('stores.branches', StoreBranchController::class)->scoped();
    Route::patch('stores/{store}/branches/{branch}/toggle-status', [StoreBranchController::class, 'toggleStatus'])->scopeBindings();

    # ----- Store Product Category Routes
    Route::get('product-categories', StoreProductCategoryController::class);

    # ----- Store Product Routes
    Route::apiResource('stores.products', StoreProductController::class)->scoped();
    Route::patch('stores/{store}/products/{product}/toggle-status', [StoreProductController::class, 'toggleStatus'])->scopeBindings();
});
