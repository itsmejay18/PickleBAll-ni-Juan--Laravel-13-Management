<?php

use App\Http\Controllers\CancellationController;
use App\Http\Controllers\CheckInController;
use App\Http\Controllers\CheckOutController;
use App\Http\Controllers\CourtController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\ModulePageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PaymentProofController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\RatingModerationController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\ReportExportController;
use App\Http\Controllers\RescheduleController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WalkInController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    $locations = \Illuminate\Support\Facades\DB::table('locations')
        ->where('is_active', true)
        ->whereNull('deleted_at')
        ->orderBy('name')
        ->get();
    return view('welcome', compact('locations'));
});

Route::get('/public/availability', [ModulePageController::class, 'getPublicAvailability'])
    ->name('public.availability');

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    // Module pages (read views)
    Route::get('/modules/{module}', ModulePageController::class)->name('modules.show');

    // Bookings & payments (existing)
    Route::post('/bookings', [ModulePageController::class, 'storeBooking'])
        ->middleware('throttle:30,1')
        ->name('bookings.store');

    Route::get('/bookings/{reservationCode}/pay', [ModulePageController::class, 'showPaymentPage'])
        ->name('bookings.pay');

    Route::post('/payments/proof', [ModulePageController::class, 'uploadPaymentProof'])
        ->middleware('throttle:20,1')
        ->name('payments.proof.store');

    Route::post('/payments/{payment}/approve', [ModulePageController::class, 'approvePayment'])->name('payments.approve');
    Route::post('/payments/{payment}/reject', [ModulePageController::class, 'rejectPayment'])->name('payments.reject');
    Route::get('/payments/{payment}/proof', [PaymentProofController::class, 'show'])
        ->name('payments.proof.download');

    Route::post('/admin/settings/gcash', [ModulePageController::class, 'updateGcashSettings'])
        ->name('admin.settings.gcash.update');

    // Locations CRUD
    Route::post('/locations', [LocationController::class, 'store'])->name('locations.store');
    Route::put('/locations/{location}', [LocationController::class, 'update'])->name('locations.update');
    Route::delete('/locations/{location}', [LocationController::class, 'destroy'])->name('locations.destroy');

    // Courts CRUD
    Route::post('/courts', [ModulePageController::class, 'storeCourt'])->name('courts.store');
    Route::put('/courts/{court}', [CourtController::class, 'update'])->name('courts.update');
    Route::delete('/courts/{court}', [CourtController::class, 'destroy'])->name('courts.destroy');
    Route::post('/courts/{court}/toggle', [CourtController::class, 'toggle'])->name('courts.toggle');

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
    Route::get('/reports/export/{type}', [ReportExportController::class, 'csv'])
        ->whereIn('type', ['revenue', 'bookings', 'cancellations', 'no-shows', 'equipment'])
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

    // Cancellation, check-in, check-out, rating
    Route::post('/reservations/{reservation}/cancel', [CancellationController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('reservations.cancel');
    Route::post('/reservations/{reservation}/check-in', [CheckInController::class, 'store'])->name('reservations.check-in');
    Route::post('/reservations/{reservation}/check-out', [CheckOutController::class, 'store'])->name('reservations.check-out');
    Route::post('/reservations/{reservation}/rate', [RatingController::class, 'store'])->name('reservations.rate');
    Route::get('/receipts/{reservation}', [ReceiptController::class, 'show'])->name('receipts.show');

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
