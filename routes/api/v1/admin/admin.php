<?php

use App\Http\Controllers\Api\V1\Admin\AdminController;
use App\Http\Controllers\Api\V1\Admin\BusinessCategoryController;
use App\Http\Controllers\Api\V1\Admin\ProductCategoryController;
use Illuminate\Support\Facades\Route;


# ----- Admin CRUD Routes
Route::apiResource('admins', AdminController::class)->except('show');
Route::patch('admins/{admin}/toggle-status', [AdminController::class, 'toggleStatus']);

# ----- Business Category Routes
Route::apiResource('business-categories', BusinessCategoryController::class)->except('show');

# ----- Product Category Routes
Route::apiResource('product-categories', ProductCategoryController::class)->except('show');
