<?php

use App\Http\Controllers\Api\V1\Public\BusinessCategoryController;
use App\Http\Controllers\Api\V1\Public\FavoriteController;
use App\Http\Controllers\Api\V1\Public\StoreBranchController;
use App\Http\Controllers\Api\V1\Public\StoreController;
use App\Http\Controllers\Api\V1\Public\StoreProductCategoryController;
use App\Http\Controllers\Api\V1\Public\StoreProductController;
use Illuminate\Support\Facades\Route;


# ----- Business Category Routes
Route::get('business-categories', BusinessCategoryController::class);

# ----- Store Product Category Routes
Route::get('stores/{store:slug}/product-categories', StoreProductCategoryController::class);

# ----- Store Routes
Route::controller(StoreController::class)->prefix('business-categories/{businessCategory:slug}/stores')->group(function () {
    Route::get('/', 'index');
    Route::get('/{store:slug}', 'show');
});

# ----- Store Branch Routes
Route::controller(StoreBranchController::class)->prefix('stores/{store:slug}/branches')->group(function () {
    Route::get('/', 'index');
    Route::get('/{branch:slug}', 'show');
});

# ----- Store Product Routes
Route::controller(StoreProductController::class)->prefix('stores/{store:slug}/products')->group(function () {
    Route::get('/', 'index');
    Route::get('/{product:slug}', 'show');
});

# ----- Favorite Routes
Route::post('favorites/toggle', [FavoriteController::class, 'toggle'])->middleware(['auth:sanctum', 'isCustomer']);