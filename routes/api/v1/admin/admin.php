<?php

use App\Http\Controllers\Api\V1\Admin\BusinessCategoryController;
use App\Http\Controllers\Api\V1\Admin\ProductCategoryController;
use Illuminate\Support\Facades\Route;


# ----- Business Category Routes
Route::apiResource('business-categories', BusinessCategoryController::class)->except('show');

# ----- Product Category Routes
Route::apiResource('product-categories', ProductCategoryController::class)->except('show');
