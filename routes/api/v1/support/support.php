<?php

use App\Http\Controllers\Api\V1\Support\AgentStatusController;
use App\Http\Controllers\Api\V1\Support\ProfileController;
use App\Http\Controllers\Api\V1\Support\TicketController;
use App\Http\Controllers\Api\V1\Support\TicketMessageController;
use Illuminate\Support\Facades\Route;

# ----- Profile Routes
Route::controller(ProfileController::class)->prefix('profile')->group(function () {
    Route::get('/', 'show');
    Route::get('/summary', 'showProfileSummary');
    Route::patch('/', 'update');
});

# ----- Agent Presence Routes
Route::controller(AgentStatusController::class)->prefix('availability')->group(function () {
    Route::get('/', 'show');
    Route::patch('/', 'update');
});

# ----- Ticket Routes
Route::controller(TicketController::class)->prefix('tickets')->group(function () {
    Route::get('/', 'index');
    Route::get('/{ticket}', 'show');
    Route::patch('/{ticket}/status', 'updateStatus');
    Route::post('/{ticket}/claim', 'claim');
    Route::delete('/{ticket}/claim', 'release');
});

# ----- Ticket Message Routes
Route::controller(TicketMessageController::class)->prefix('tickets/{ticket}')->group(function () {
    Route::get('messages', 'index');
    Route::post('messages', 'store');
    Route::post('read', 'markRead');
});
