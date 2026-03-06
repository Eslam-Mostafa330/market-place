<?php

use App\Http\Controllers\Api\V1\Admin\BusinessCategoryController;
use Illuminate\Support\Facades\Route;


# ----- Business Category Routes
Route::apiResource('business-categories', BusinessCategoryController::class)->except('show');
