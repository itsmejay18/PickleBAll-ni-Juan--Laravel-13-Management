<?php

use App\Http\Controllers\CancellationController;
use App\Http\Controllers\CheckInController;
use App\Http\Controllers\CheckOutController;
use App\Http\Controllers\CourtController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\GcashQrController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\ModulePageController;
use App\Http\Controllers\OpenPlayController;use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentProofController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\RatingModerationController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\ReportExportController;
use App\Http\Controllers\RescheduleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WalkInController;
use App\Http\Controllers\XPayLinkController;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $locations = DB::table('locations')
        ->where('is_active', true)
        ->whereNull('deleted_at')
        ->orderBy('name')
        ->get();

    // Live court rates (per hour) — reflects super-admin pricing changes instantly.
    $courtRates = DB::table('courts as c')
        ->join('locations as l', 'l.id', '=', 'c.location_id')
        ->leftJoin('court_pricing_rules as pr', function ($j) {
            $j->on('pr.court_id', '=', 'c.id')->where('pr.is_active', '=', true);
        })
        ->where('c.is_active', true)->whereNull('c.deleted_at')
        ->where('l.is_active', true)->whereNull('l.deleted_at')
        ->groupBy('c.id', 'c.court_name', 'c.court_number', 'l.name')
        ->orderBy('l.name')->orderBy('c.court_number')
        ->get([
            'c.court_name', 'c.court_number', 'l.name as location_name',
            DB::raw('MIN(pr.base_price) as base_price'),
        ]);

    // Live equipment rates (paddle/ball/etc, incl. old/new variants set in admin).
    $equipmentRates = DB::table('equipment_types')
        ->where('is_available_for_rent', true)
        ->whereNull('deleted_at')
        ->orderBy('display_order')->orderBy('name')
        ->get(['name', 'description', 'rental_price_per_unit', 'deposit_amount']);

    $publicSettings = [
        'playing_open_time' => SystemSetting::value('public_playing_open_time', '07:00'),
        'playing_close_time' => SystemSetting::value('public_playing_close_time', '00:00'),
        'facebook_url' => SystemSetting::value('public_facebook_url', 'https://www.facebook.com/profile.php?id=61584658084190'),
        'email' => SystemSetting::value('public_contact_email', 'cajpulido@yahoo.com'),
        'phone' => SystemSetting::value('public_contact_phone', '09383427139'),
        'developer_name' => SystemSetting::value('public_developer_name', 'RestBack'),
        'developer_url' => SystemSetting::value('public_developer_url', 'https://www.facebook.com/restback200/'),
    ];

    $totalReservationsCount = DB::table('reservations')->whereNull('deleted_at')->count();

    // Open Play card for the public dashboard (only when enabled).
    $openPlay = \App\Models\OpenPlayEvent::upcoming();

    return view('welcome', compact('locations', 'courtRates', 'equipmentRates', 'publicSettings', 'totalReservationsCount', 'openPlay'));
});

// NOTE: path must NOT start with /public — on shared hosting the physical
// `public/` directory intercepts /public/* before Laravel routing (404).
Route::get('/court-availability', [ModulePageController::class, 'getPublicAvailability'])
    ->name('public.availability');

// Serve the owner GCash QR through PHP so it renders without a storage symlink.
Route::get('/gcash-qr', [GcashQrController::class, 'show'])->name('public.gcash-qr');

Route::post('/payments/xpaylink/webhook', [XPayLinkController::class, 'webhook'])->name('payments.xpaylink.webhook');

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    // Module pages (read views)
    Route::get('/modules/{module}', ModulePageController::class)->name('modules.show');

    // Open Play
    Route::get('/open-play', [OpenPlayController::class, 'index'])->name('open-play.index');
    Route::get('/open-play/manage', [OpenPlayController::class, 'manage'])->name('open-play.manage');
    Route::post('/open-play/settings', [OpenPlayController::class, 'updateSettings'])->name('open-play.settings.update');
    Route::post('/open-play/{event}/join', [OpenPlayController::class, 'join'])->middleware('throttle:30,1')->name('open-play.join');
    Route::get('/open-play/{event}/slots', [OpenPlayController::class, 'slots'])->name('open-play.slots');
    Route::get('/open-play/{event}/export', [OpenPlayController::class, 'export'])->name('open-play.export');
    Route::post('/open-play/{event}/generate', [OpenPlayController::class, 'generate'])->name('open-play.matches.generate');
    Route::post('/open-play/{event}/regenerate', [OpenPlayController::class, 'regenerate'])->name('open-play.matches.regenerate');
    Route::get('/open-play/ticket/{registration}', [OpenPlayController::class, 'ticket'])->name('open-play.ticket');
    Route::post('/open-play/registrations/{registration}/check-in', [OpenPlayController::class, 'checkIn'])->name('open-play.checkin');
    Route::delete('/open-play/registrations/{registration}', [OpenPlayController::class, 'removeParticipant'])->name('open-play.participant.remove');
    Route::post('/open-play/{event}/self-checkin', [OpenPlayController::class, 'selfCheckIn'])->name('open-play.self-checkin');
    Route::post('/open-play/matches/{match}/result', [OpenPlayController::class, 'submitMatchResult'])->name('open-play.submit-result');
    Route::get('/open-play/tv', [OpenPlayController::class, 'tv'])->name('open-play.tv');
    Route::get('/open-play/leaderboard', [OpenPlayController::class, 'leaderboard'])->name('open-play.leaderboard');
    Route::get('/open-play/registrations/{registration}/pay', [OpenPlayController::class, 'payRegistration'])->name('open-play.pay');
    Route::get('/open-play/registrations/{registration}/status', [OpenPlayController::class, 'registrationStatus'])->name('open-play.registration-status');
    Route::post('/open-play/{event}/register-cash', [OpenPlayController::class, 'registerCash'])->name('open-play.register-cash');
    Route::post('/open-play/registrations/{registration}/mark-cash-paid', [OpenPlayController::class, 'markCashPaid'])->name('open-play.mark-cash-paid');

    // Bookings & payments (existing)
    Route::post('/bookings', [ModulePageController::class, 'storeBooking'])
        ->middleware('throttle:30,1')
        ->name('bookings.store');
    Route::post('/bookings/calculate-price', [ModulePageController::class, 'calculatePrice'])->name('bookings.calculate-price');
    Route::get('/bookings/{reservationCode}/pay', [ModulePageController::class, 'showPaymentPage'])
        ->name('bookings.pay');

    Route::post('/payments/{reservation}/xpaylink/redirect', [XPayLinkController::class, 'redirect'])->name('payments.xpaylink.redirect');

    Route::post('/payments/proof', [ModulePageController::class, 'uploadPaymentProof'])
        ->middleware('throttle:20,1')
        ->name('payments.proof.store');

    Route::post('/payments/{payment}/approve', [ModulePageController::class, 'approvePayment'])->name('payments.approve');
    Route::post('/payments/{payment}/reject', [ModulePageController::class, 'rejectPayment'])->name('payments.reject');
    Route::get('/payments/{payment}/proof', [PaymentProofController::class, 'show'])
        ->name('payments.proof.download');

    Route::post('/admin/settings/gcash', [ModulePageController::class, 'updateGcashSettings'])
        ->name('admin.settings.gcash.update');

    Route::post('/admin/settings/public-site', [ModulePageController::class, 'updatePublicSiteSettings'])
        ->name('admin.settings.public-site.update');

    // Locations CRUD
    Route::post('/locations', [LocationController::class, 'store'])->name('locations.store');
    Route::put('/locations/{location}', [LocationController::class, 'update'])->name('locations.update');
    Route::delete('/locations/{location}', [LocationController::class, 'destroy'])->name('locations.destroy');

    // Courts CRUD
    Route::post('/courts', [ModulePageController::class, 'storeCourt'])->name('courts.store');
    Route::put('/courts/{court}', [CourtController::class, 'update'])->name('courts.update');
    Route::delete('/courts/{court}', [CourtController::class, 'destroy'])->name('courts.destroy');
    Route::post('/courts/{court}/toggle', [CourtController::class, 'toggle'])->name('courts.toggle');
    Route::get('/courts/{court}/rates', [CourtController::class, 'getRates'])->name('courts.rates.index');
    Route::post('/courts/{court}/rates', [CourtController::class, 'addRate'])->name('courts.rates.store');
    Route::put('/courts/rates/{rate}', [CourtController::class, 'updateRate'])->name('courts.rates.update');
    Route::delete('/courts/rates/{rate}', [CourtController::class, 'deleteRate'])->name('courts.rates.destroy');

    // Equipment CRUD
    Route::post('/equipment', [ModulePageController::class, 'storeEquipment'])->name('equipment.store');
    Route::put('/equipment/{inventory}', [EquipmentController::class, 'update'])->name('equipment.update');
    Route::delete('/equipment/{inventory}', [EquipmentController::class, 'destroy'])->name('equipment.destroy');

    // Court maintenance windows (B5-B7)
    Route::post('/maintenance', [MaintenanceController::class, 'store'])->name('maintenance.store');
    Route::put('/maintenance/{maintenance}', [MaintenanceController::class, 'update'])->name('maintenance.update');
    Route::delete('/maintenance/{maintenance}', [MaintenanceController::class, 'destroy'])->name('maintenance.destroy');

    // Review moderation (K7-K8, K10)
    Route::post('/ratings/{rating}/respond', [RatingModerationController::class, 'respond'])->name('ratings.respond');
    Route::post('/ratings/{rating}/hide', [RatingModerationController::class, 'hide'])->name('ratings.hide');
    Route::post('/ratings/{rating}/approve', [RatingModerationController::class, 'approve'])->name('ratings.approve');
    Route::post('/ratings/{rating}/adjust', [RatingModerationController::class, 'adjust'])->name('ratings.adjust');

    // Report exports (O10)
    Route::get('/reports/sales/pdf', [ReportExportController::class, 'salesPdf'])->name('reports.sales.pdf');
    Route::get('/reports/export/{type}', [ReportExportController::class, 'csv'])
        ->whereIn('type', ['revenue', 'bookings', 'sales', 'cancellations', 'no-shows', 'equipment'])
        ->name('reports.export');

    // User management (L5, A3)
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::post('/users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle-active');
    Route::post('/users/{user}/password-reset', [UserController::class, 'sendPasswordReset'])->name('users.password-reset');

    // Walk-in booking
    Route::post('/walk-ins', [WalkInController::class, 'store'])
        ->middleware('throttle:30,1')
        ->name('walk-ins.store');
    Route::post('/walk-ins/{reservation}/mark-paid', [WalkInController::class, 'markPaid'])
        ->name('walk-ins.mark-paid');

    // Cancellation, check-in, check-out, rating
    Route::post('/reservations/{reservation}/cancel', [CancellationController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('reservations.cancel');
    Route::post('/reservations/{reservation}/check-in', [CheckInController::class, 'store'])->name('reservations.check-in');
    Route::post('/reservations/{reservation}/check-out', [CheckOutController::class, 'store'])->name('reservations.check-out');
    Route::post('/reservations/{reservation}/rate', [RatingController::class, 'store'])->name('reservations.rate');
    Route::get('/receipts/{reservation}', [ReceiptController::class, 'show'])->name('receipts.show');
    Route::post('/receipts/{reservation}/confirm', [ReceiptController::class, 'confirm'])->name('receipts.confirm');
    Route::delete('/receipts/{reservation}', [ReceiptController::class, 'destroy'])->name('receipts.destroy');
    Route::post('/receipts/bulk-delete', [ReceiptController::class, 'bulkDelete'])->name('receipts.bulk-delete');

    // Reschedule management
    Route::post('/reservations/{reservation}/reschedule/lock', [RescheduleController::class, 'lock'])
        ->name('reservations.reschedule.lock');
    Route::post('/reservations/{reservation}/reschedule/unlock', [RescheduleController::class, 'unlock'])
        ->name('reservations.reschedule.unlock');
    Route::get('/reservations/{reservation}/reschedule/status', [RescheduleController::class, 'status'])
        ->name('reservations.reschedule.status');
    Route::post('/reservations/{reservation}/reschedule', [RescheduleController::class, 'reschedule'])
        ->middleware('throttle:20,1')
        ->name('reservations.reschedule');

    // Notifications
    Route::get('/notifications/feed', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications', [NotificationController::class, 'show'])->name('notifications.show');
    Route::post('/notifications/{notification}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.read-all');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
