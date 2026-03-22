<?php

use App\Http\Controllers\Api\V1\Public\BusinessCategoryController;
use App\Http\Controllers\Api\V1\Public\StoreBranchController;
use App\Http\Controllers\Api\V1\Public\StoreController;
use Illuminate\Support\Facades\Route;


# ----- Business Category Routes
Route::get('business-categories', BusinessCategoryController::class);

# ----- Store Routes
Route::controller(StoreController::class)->prefix('business-categories/{businessCategory:slug}/stores')->group(function () {
    Route::get('/', 'index');
    Route::get('/{store:slug}', 'show');
});
