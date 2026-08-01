<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\GtfsController as AdminGtfsController;
use App\Http\Controllers\Admin\RoadController as AdminRoadController;
use App\Http\Controllers\Admin\SubsidyController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\VerificationController as AdminVerificationController;
use App\Http\Controllers\Admin\WorkplaceController as AdminWorkplaceController;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\BookingController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\GtfsController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\ImpactCertificateController;
use App\Http\Controllers\Web\ImpactController;
use App\Http\Controllers\Web\PaystackWebhookController;
use App\Http\Controllers\Web\PwaController;
use App\Http\Controllers\Web\RoadMapController;
use App\Http\Controllers\Web\TripBoardController;
use App\Http\Controllers\Web\VerificationController;
use App\Http\Controllers\Web\WalletController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

// PWA shell — installable manifest + offline service worker.
Route::get('/manifest.json', [PwaController::class, 'manifest'])->name('pwa.manifest');
Route::get('/sw.js', [PwaController::class, 'serviceWorker'])->name('pwa.sw');

// Public Road Intelligence heatmap — confirmed potholes + segment condition.
Route::get('/road/map', RoadMapController::class)->name('road.map');

// Public certificate verification — the QR on every impact certificate decodes here.
Route::get('/impact/verify/{user}/{type}', [ImpactCertificateController::class, 'verify'])->name('impact.verify');

Route::post('/paystack/webhook', [PaystackWebhookController::class, 'handle'])->name('paystack.webhook');

// Public GTFS endpoints — static feed zip + GTFS-realtime for Google Transit.
Route::prefix('gtfs')->name('gtfs.')->group(function () {
    Route::get('/gtfs.zip', [GtfsController::class, 'feed'])->name('feed');
    Route::get('/gtfs-rt/vehicle_positions.pb', [GtfsController::class, 'vehiclePositions'])->name('vehicle_positions');
    Route::get('/gtfs-rt/trip_updates.pb', [GtfsController::class, 'tripUpdates'])->name('trip_updates');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);

    Route::get('/auth/google/redirect', [AuthController::class, 'redirectToGoogle'])->name('auth.google');
    Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::prefix('impact')->name('impact.')->group(function () {
        Route::get('/', [ImpactController::class, 'index'])->name('index');
        Route::get('/certificate/{type}', [ImpactCertificateController::class, 'show'])->name('certificate');
    });

    Route::prefix('wallet')->name('wallet.')->group(function () {
        Route::get('/', [WalletController::class, 'index'])->name('index');
        Route::post('/topup', [WalletController::class, 'topUp'])->name('topup');
    });

    Route::prefix('verify')->name('verification.')->group(function () {
        Route::get('/', [VerificationController::class, 'index'])->name('index');
        Route::post('/workplace', [VerificationController::class, 'storeWorkplace'])->name('workplace');
        Route::post('/nin', [VerificationController::class, 'storeNin'])->name('nin');
    });

    Route::resource('trips', TripBoardController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('/trips/{trip}/start', [TripBoardController::class, 'start'])->name('trips.start');
    Route::post('/trips/{trip}/complete', [TripBoardController::class, 'complete'])->name('trips.complete');
    Route::post('/trips/{trip}/cancel', [TripBoardController::class, 'cancel'])->name('trips.cancel');
    Route::post('/trips/{trip}/messages', [TripBoardController::class, 'storeMessage'])->name('trips.messages');

    Route::prefix('bookings')->name('bookings.')->group(function () {
        Route::get('/', [BookingController::class, 'index'])->name('index');
        Route::post('/{booking}/cancel', [BookingController::class, 'cancel'])->name('cancel');
        Route::post('/{booking}/board', [BookingController::class, 'board'])->name('board');
        Route::post('/{booking}/no-show', [BookingController::class, 'noShow'])->name('no-show');
    });
    Route::post('/trips/{trip}/book', [BookingController::class, 'book'])->name('bookings.store');

    Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('dashboard');

        Route::get('/verifications', [AdminVerificationController::class, 'index'])->name('verifications.index');
        Route::post('/verifications/{verification}/approve', [AdminVerificationController::class, 'approve'])->name('verifications.approve');
        Route::post('/verifications/{verification}/reject', [AdminVerificationController::class, 'reject'])->name('verifications.reject');

        Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
        Route::post('/users/{user}/ban', [AdminUserController::class, 'ban'])->name('users.ban');
        Route::post('/users/{user}/unban', [AdminUserController::class, 'unban'])->name('users.unban');

        Route::get('/workplaces', [AdminWorkplaceController::class, 'index'])->name('workplaces.index');

        Route::get('/subsidies', [SubsidyController::class, 'index'])->name('subsidies.index');
        Route::post('/subsidies/credit', [SubsidyController::class, 'bulkCredit'])->name('subsidies.credit');

        Route::get('/gtfs', [AdminGtfsController::class, 'index'])->name('gtfs.index');
        Route::post('/gtfs/regenerate', [AdminGtfsController::class, 'regenerate'])->name('gtfs.regenerate');

        Route::get('/road', [AdminRoadController::class, 'index'])->name('road.index');
        Route::get('/road/export', [AdminRoadController::class, 'export'])->name('road.export');
    });
});
