<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\Admin\BusinessController;
use App\Http\Controllers\Admin\EmployerController;
use App\Http\Controllers\Admin\FleetController;
use App\Http\Controllers\Admin\ForecastController;
use App\Http\Controllers\Admin\GtfsController as AdminGtfsController;
use App\Http\Controllers\Admin\MissionController as AdminMissionController;
use App\Http\Controllers\Admin\OpsController;
use App\Http\Controllers\Admin\RatingController as AdminRatingController;
use App\Http\Controllers\Admin\RewardController as AdminRewardController;
use App\Http\Controllers\Admin\RoadController as AdminRoadController;
use App\Http\Controllers\Admin\ScoreboardController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\StakeholderController;
use App\Http\Controllers\Admin\SubsidyController;
use App\Http\Controllers\Admin\TrustController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\VerificationController as AdminVerificationController;
use App\Http\Controllers\Admin\WorkplaceController as AdminWorkplaceController;
use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\BookingController;
use App\Http\Controllers\Web\CommodityController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\DemandController;
use App\Http\Controllers\Web\DriverFleetController;
use App\Http\Controllers\Web\EmployerRequestController;
use App\Http\Controllers\Web\GtfsController;
use App\Http\Controllers\Web\GuideController;
use App\Http\Controllers\Web\HomeController;
use App\Http\Controllers\Web\ImpactCertificateController;
use App\Http\Controllers\Web\ImpactController;
use App\Http\Controllers\Web\MissionController;
use App\Http\Controllers\Web\NavigationController;
use App\Http\Controllers\Web\PaystackWebhookController;
use App\Http\Controllers\Web\ProfileController;
use App\Http\Controllers\Web\PwaController;
use App\Http\Controllers\Web\RatingController;
use App\Http\Controllers\Web\ReceiptController;
use App\Http\Controllers\Web\RewardsController;
use App\Http\Controllers\Web\RoadMapController;
use App\Http\Controllers\Web\SafetyController;
use App\Http\Controllers\Web\ShopController;
use App\Http\Controllers\Web\TripBoardController;
use App\Http\Controllers\Web\VerificationController;
use App\Http\Controllers\Web\WalletController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

// PWA shell — installable manifest + offline service worker.
Route::get('/manifest.json', [PwaController::class, 'manifest'])->name('pwa.manifest');
Route::get('/sw.js', [PwaController::class, 'serviceWorker'])->name('pwa.sw');

// Offline fallback page served by the service worker when the network drops.
Route::get('/offline', fn () => view('offline'))->name('offline');

// Public trip share page — "send this ride to your colleague".
Route::get('/trips/{trip}/share', [SafetyController::class, 'share'])->name('trips.share');

// Public Road Intelligence heatmap — confirmed potholes + segment condition.
Route::get('/road/map', RoadMapController::class)->name('road.map');

// Public certificate verification — the QR on every impact certificate decodes here.
Route::get('/impact/verify/{user}/{type}', [ImpactCertificateController::class, 'verify'])->name('impact.verify');

// Public receipt verification — the QR on every receipt decodes here.
Route::get('/receipts/verify/{type}/{reference}', [ReceiptController::class, 'verify'])->name('receipts.verify');

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

    // Navigation-first rider home — "Where are you going?" (navigation sprint).
    Route::get('/go', NavigationController::class)->name('go');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::post('/ratings/{booking}', [RatingController::class, 'store'])->name('ratings.store');

    Route::prefix('impact')->name('impact.')->group(function () {
        Route::get('/', [ImpactController::class, 'index'])->name('index');
        Route::get('/certificate/{type}', [ImpactCertificateController::class, 'show'])->name('certificate');
    });

    Route::prefix('wallet')->name('wallet.')->group(function () {
        Route::get('/', [WalletController::class, 'index'])->name('index');
        Route::post('/topup', [WalletController::class, 'topUp'])->name('topup');
        Route::post('/transfer', [WalletController::class, 'transfer'])->name('transfer');
        Route::post('/withdraw', [WalletController::class, 'withdraw'])->name('withdraw');
    });

    Route::prefix('rewards')->name('rewards.')->group(function () {
        Route::get('/', [RewardsController::class, 'index'])->name('index');
        Route::post('/redeem', [RewardsController::class, 'redeem'])->name('redeem');
    });

    Route::prefix('commodities')->name('commodities.')->group(function () {
        Route::get('/', [CommodityController::class, 'index'])->name('index');
        Route::post('/buy', [CommodityController::class, 'buy'])->name('buy');
        Route::post('/sell', [CommodityController::class, 'sell'])->name('sell');
    });

    Route::prefix('shop')->name('shop.')->group(function () {
        Route::get('/', [ShopController::class, 'index'])->name('index');
        Route::post('/orders', [ShopController::class, 'store'])->name('store');
        Route::post('/orders/{order}/cancel', [ShopController::class, 'cancel'])->name('cancel');
    });

    Route::prefix('verify')->name('verification.')->group(function () {
        Route::get('/', [VerificationController::class, 'index'])->name('index');
        Route::get('/phone', [VerificationController::class, 'phone'])->name('phone');
        Route::post('/phone', [VerificationController::class, 'sendPhoneOtp'])->name('phone.send');
        Route::post('/phone/verify', [VerificationController::class, 'verifyPhone'])->name('phone.verify');
        Route::post('/workplace', [VerificationController::class, 'storeWorkplace'])->name('workplace');
        Route::post('/nin', [VerificationController::class, 'storeNin'])->name('nin');
    });

    // Employer mobility, rider side (guide §7 Form 1) + self-service vehicles.
    Route::get('/profile/employers', [EmployerRequestController::class, 'employers'])->name('employers.self');
    Route::post('/employers/{employer}/join', [EmployerRequestController::class, 'join'])->name('employers.join');
    Route::get('/employer/vehicles', [EmployerRequestController::class, 'vehicles'])->name('employer.vehicles');
    Route::post('/employer/vehicles', [EmployerRequestController::class, 'storeVehicle'])->name('employer.vehicles.store');
    Route::delete('/employer/vehicles/{vehicle}', [EmployerRequestController::class, 'destroyVehicle'])->name('employer.vehicles.destroy');

    Route::prefix('missions')->name('missions.')->group(function () {
        Route::get('/', [MissionController::class, 'index'])->name('index');
        Route::post('/{mission}/proof', [MissionController::class, 'submitProof'])->name('proof');
    });

    // Rider demand check-in (guide §9B Method 5) — "I'm at Berger, need a ride".
    Route::prefix('demand')->name('demand.')->group(function () {
        Route::get('/', [DemandController::class, 'index'])->name('index');
        Route::post('/checkin', [DemandController::class, 'checkIn'])->name('checkin');
    });

    // Driver fleet (guide §11) — daily pre-trip inspection, fault reporting.
    Route::prefix('fleet')->name('fleet.')->group(function () {
        Route::get('/', [DriverFleetController::class, 'index'])->name('index');
        Route::post('/{asset}/inspect', [DriverFleetController::class, 'inspect'])->name('inspect');
        Route::post('/{asset}/faults', [DriverFleetController::class, 'storeFault'])->name('faults');
    });

    Route::prefix('receipts')->name('receipts.')->group(function () {
        Route::get('/booking/{booking}', [ReceiptController::class, 'booking'])->name('booking');
        Route::get('/earnings/{booking}', [ReceiptController::class, 'earnings'])->name('earnings');
        Route::get('/topup/{transaction}', [ReceiptController::class, 'topup'])->name('topup');
        Route::get('/subsidy/{transaction}', [ReceiptController::class, 'subsidy'])->name('subsidy');
        Route::get('/statement/{month}', [ReceiptController::class, 'statement'])->name('statement');
    });

    Route::resource('trips', TripBoardController::class)->only(['index', 'create', 'store', 'show']);
    Route::post('/trips/{trip}/start', [TripBoardController::class, 'start'])->name('trips.start');
    Route::post('/trips/{trip}/complete', [TripBoardController::class, 'complete'])->name('trips.complete');
    Route::post('/trips/{trip}/cancel', [TripBoardController::class, 'cancel'])->name('trips.cancel');
    Route::post('/trips/{trip}/messages', [TripBoardController::class, 'storeMessage'])->name('trips.messages');
    Route::post('/trips/{trip}/interest', [TripBoardController::class, 'registerInterest'])->name('trips.interest');
    Route::post('/trips/{trip}/sos', [SafetyController::class, 'sos'])->name('trips.sos');

    Route::prefix('trips/{trip}/guide')->name('trips.guide.')->group(function () {
        Route::get('/', [GuideController::class, 'show'])->name('show');
        Route::get('/route', [GuideController::class, 'route'])->name('route');
    });

    Route::prefix('bookings')->name('bookings.')->group(function () {
        Route::get('/', [BookingController::class, 'index'])->name('index');
        Route::post('/{booking}/cancel', [BookingController::class, 'cancel'])->name('cancel');
        Route::post('/{booking}/board', [BookingController::class, 'board'])->name('board');
        Route::post('/{booking}/no-show', [BookingController::class, 'noShow'])->name('no-show');
        Route::post('/{booking}/approve', [BookingController::class, 'approve'])->name('approve');
        Route::post('/{booking}/decline', [BookingController::class, 'decline'])->name('decline');
    });
    Route::post('/trips/{trip}/book', [BookingController::class, 'book'])->name('bookings.store');
    Route::post('/trips/{trip}/request', [BookingController::class, 'request'])->name('bookings.request');

    Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
        Route::get('/', [AdminController::class, 'index'])->name('dashboard');
        Route::post('/view-as', [AdminController::class, 'viewAs'])->name('view-as');
        Route::post('/view-as/reset', [AdminController::class, 'resetViewAs'])->name('view-as.reset');

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

        Route::get('/business', [BusinessController::class, 'index'])->name('business.index');
        Route::get('/business/export/transactions', [BusinessController::class, 'exportTransactions'])->name('business.export.transactions');
        Route::get('/business/export/settlements', [BusinessController::class, 'exportSettlements'])->name('business.export.settlements');
        Route::get('/business/export/subsidy', [BusinessController::class, 'exportSubsidy'])->name('business.export.subsidy');

        Route::get('/ops/demand', [OpsController::class, 'index'])->name('ops.demand');

        Route::get('/fleet', [FleetController::class, 'index'])->name('fleet.index');
        Route::post('/fleet/{asset}/inspect', [FleetController::class, 'recordInspection'])->name('fleet.inspect');
        Route::post('/fleet/{asset}/schedule', [FleetController::class, 'scheduleMaintenance'])->name('fleet.schedule');
        Route::post('/faults/{fault}/resolve', [FleetController::class, 'resolveFault'])->name('faults.resolve');
        Route::post('/maintenance/{schedule}/complete', [FleetController::class, 'completeMaintenance'])->name('maintenance.complete');

        Route::get('/forecasts', [ForecastController::class, 'index'])->name('forecasts.index');
        Route::post('/forecasts', [ForecastController::class, 'store'])->name('forecasts.store');
        Route::post('/forecasts/train', [ForecastController::class, 'train'])->name('forecasts.train');

        Route::get('/stakeholders', [StakeholderController::class, 'index'])->name('stakeholders.index');
        Route::post('/stakeholders/settle', [StakeholderController::class, 'settle'])->name('stakeholders.settle');

        Route::get('/driver-scores', [ScoreboardController::class, 'index'])->name('scoreboard.index');
        Route::post('/driver-scores/run', [ScoreboardController::class, 'run'])->name('scoreboard.run');

        Route::get('/trust', [TrustController::class, 'index'])->name('trust.index');
        Route::get('/trust/export', [TrustController::class, 'export'])->name('trust.export');
        Route::get('/trust/pay-it-forward', [TrustController::class, 'payItForward'])->name('trust.pay-it-forward');
        Route::get('/trust/pay-it-forward/export', [TrustController::class, 'exportPayItForward'])->name('trust.pay-it-forward.export');

        Route::get('/ratings', [AdminRatingController::class, 'index'])->name('ratings.index');

        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::post('/settings', [SettingsController::class, 'store'])->name('settings.store');

        Route::get('/employers', [EmployerController::class, 'index'])->name('employers.index');
        Route::get('/employers/create', [EmployerController::class, 'create'])->name('employers.create');
        Route::post('/employers', [EmployerController::class, 'store'])->name('employers.store');
        Route::get('/employers/members/pending', [EmployerController::class, 'pendingMembers'])->name('employers.members.pending');
        Route::get('/employers/{employer}', [EmployerController::class, 'show'])->name('employers.show');
        Route::get('/employers/{employer}/report', [EmployerController::class, 'report'])->name('employers.report');
        Route::get('/employers/{employer}/members', [EmployerController::class, 'members'])->name('employers.members');
        Route::get('/employers/{employer}/vehicles', [EmployerController::class, 'vehicles'])->name('employers.vehicles');
        Route::post('/employers/{employer}/fund', [EmployerController::class, 'fund'])->name('employers.fund');
        Route::post('/employers/{employer}/enroll', [EmployerController::class, 'enroll'])->name('employers.enroll');
        Route::put('/employer-members/{member}/approve', [EmployerController::class, 'approveMember'])->name('employers.members.approve');
        Route::put('/employer-members/{member}/reject', [EmployerController::class, 'rejectMember'])->name('employers.members.reject');
        Route::put('/employer-members/{member}/review', [EmployerController::class, 'reviewMember'])->name('employers.members.review');

        Route::get('/rewards', [AdminRewardController::class, 'index'])->name('rewards.index');
        Route::get('/rewards/create', [AdminRewardController::class, 'create'])->name('rewards.create');
        Route::post('/rewards', [AdminRewardController::class, 'store'])->name('rewards.store');
        Route::post('/rewards/{campaign}/toggle', [AdminRewardController::class, 'toggle'])->name('rewards.toggle');

        Route::get('/missions', [AdminMissionController::class, 'index'])->name('missions.index');
        Route::get('/missions/create', [AdminMissionController::class, 'create'])->name('missions.create');
        Route::post('/missions', [AdminMissionController::class, 'store'])->name('missions.store');
        Route::get('/missions/{mission}', [AdminMissionController::class, 'show'])->name('missions.show');
        Route::post('/missions/{mission}/toggle', [AdminMissionController::class, 'toggle'])->name('missions.toggle');
        Route::post('/missions/submissions/{submission}/approve', [AdminMissionController::class, 'approveSubmission'])->name('missions.approve');
        Route::post('/missions/submissions/{submission}/reject', [AdminMissionController::class, 'rejectSubmission'])->name('missions.reject');
    });
});
