<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\ChatController;
use App\Http\Controllers\Api\V1\DemandController;
use App\Http\Controllers\Api\V1\P2pTransferController;
use App\Http\Controllers\Api\V1\RideCreditController;
use App\Http\Controllers\Api\V1\RoadSensorController;
use App\Http\Controllers\Api\V1\SmileWebhookController;
use App\Http\Controllers\Api\V1\TripController;
use App\Http\Controllers\Api\V1\VerificationController;
use App\Http\Controllers\Api\V1\WalletController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    // Public, anonymised road data for the heatmap (lat/lng/severity only).
    Route::get('/road-events', [RoadSensorController::class, 'index']);

    // Smile Identity result callback — signature-verified, public like Paystack.
    Route::post('/webhooks/smile', [SmileWebhookController::class, 'handle']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        Route::get('/wallet', [WalletController::class, 'index']);
        Route::post('/wallet/topup', [WalletController::class, 'topUp']);
        Route::post('/wallet/withdraw', [WalletController::class, 'withdraw']);

        Route::post('/wallet/transfer', [P2pTransferController::class, 'store']);
        Route::get('/wallet/transfers', [P2pTransferController::class, 'index']);

        Route::get('/ride-credits', [RideCreditController::class, 'index']);

        Route::get('/verifications', [VerificationController::class, 'index']);
        Route::get('/verifications/status', [VerificationController::class, 'status']);
        Route::post('/verifications/workplace', [VerificationController::class, 'submitWorkplace']);
        Route::post('/verifications/nin', [VerificationController::class, 'submitNin']);

        // Sprint 3.6 tiered KYC — feature-gated on FEATURE_LIVENESS.
        Route::post('/verifications/tier1', [VerificationController::class, 'tier1']);
        Route::post('/verifications/tier2', [VerificationController::class, 'tier2']);
        Route::post('/verifications/tier3', [VerificationController::class, 'tier3']);

        Route::post('/road-events', [RoadSensorController::class, 'store']);

        // Demand research field kit (guide §9B) — junction counts, rider check-ins, probe dwells.
        Route::post('/demand/surveys', [DemandController::class, 'surveys']);
        Route::post('/demand/checkins', [DemandController::class, 'checkIns']);
        Route::post('/demand/probes', [DemandController::class, 'probes']);

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
