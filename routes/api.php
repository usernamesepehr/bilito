<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::middleware('throttle:register')->post('register', [AuthController::class, 'register']);
        Route::middleware('throttle:login')->post('login', [AuthController::class, 'login']);
        Route::middleware('throttle:refresh')->post('refresh', [AuthController::class, 'refresh']);

        Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('me', [AuthController::class, 'me']);
        });
    });
});
