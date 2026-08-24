<?php

use App\Http\Controllers\Api\InternalApiController;
use Illuminate\Support\Facades\Route;

Route::middleware(['internal.secret'])->prefix('internal')->group(function () {
    Route::get('/servers', [InternalApiController::class, 'getActiveServers']);
    Route::post('/servers/{id}/status', [InternalApiController::class, 'updateStatus']);
    Route::post('/servers/{id}/sync', [InternalApiController::class, 'syncStates']);
});
