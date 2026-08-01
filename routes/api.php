<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\ChatController;
use App\Http\Controllers\Api\V1\TripController;
use App\Http\Controllers\Api\V1\VerificationController;
use App\Http\Controllers\Api\V1\WalletController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        Route::get('/wallet', [WalletController::class, 'index']);
        Route::post('/wallet/topup', [WalletController::class, 'topUp']);

        Route::get('/verifications', [VerificationController::class, 'index']);
        Route::post('/verifications/workplace', [VerificationController::class, 'submitWorkplace']);
        Route::post('/verifications/nin', [VerificationController::class, 'submitNin']);

        Route::get('/trips', [TripController::class, 'index']);
        Route::post('/trips', [TripController::class, 'store']);
        Route::get('/trips/{trip}', [TripController::class, 'show']);
        Route::patch('/trips/{trip}/location', [TripController::class, 'updateLocation']);
        Route::post('/trips/{trip}/start', [TripController::class, 'start']);
        Route::post('/trips/{trip}/complete', [TripController::class, 'complete']);
        Route::post('/trips/{trip}/cancel', [TripController::class, 'cancel']);

        Route::middleware('verified.worker')->group(function () {
            Route::get('/bookings', [BookingController::class, 'index']);
            Route::post('/trips/{trip}/bookings', [BookingController::class, 'store']);
            Route::post('/bookings/{booking}/cancel', [BookingController::class, 'cancel']);
            Route::post('/bookings/{booking}/board', [BookingController::class, 'board']);
            Route::post('/bookings/{booking}/no-show', [BookingController::class, 'noShow']);

            Route::get('/trips/{trip}/messages', [ChatController::class, 'index']);
            Route::post('/trips/{trip}/messages', [ChatController::class, 'store']);
        });
    });
});
