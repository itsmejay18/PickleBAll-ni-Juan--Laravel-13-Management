<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Services\AuditService;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RescheduleController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    /**
     * Lock reschedule for a reservation (Super Admin, Admin, Staff).
     * Single-click — no reason required.
     */
    public function lock(Request $request, Reservation $reservation): JsonResponse
    {
        abort_unless(
            $request->user()->hasAnyRole(['super_admin', 'admin', 'staff', 'location_manager']),
            403,
            'Unauthorized action.'
        );

        $reservation->update([
            'reschedule_locked' => true,
            'reschedule_locked_by' => $request->user()->id,
            'reschedule_locked_at' => now(),
        ]);

        return response()->json([
            'message' => 'Reschedule has been locked for this reservation.',
            'reservation' => $reservation->only(['id', 'reservation_code', 'reschedule_locked', 'reschedule_locked_at']),
        ]);
    }

    /**
     * Unlock reschedule for a reservation (Super Admin, Admin, Staff).
     * Single-click — no reason required.
     */
    public function unlock(Request $request, Reservation $reservation): JsonResponse
    {
        abort_unless(
            $request->user()->hasAnyRole(['super_admin', 'admin', 'staff', 'location_manager']),
            403,
            'Unauthorized action.'
        );

        $reservation->update([
            'reschedule_locked' => false,
            'reschedule_locked_by' => null,
            'reschedule_locked_at' => null,
        ]);

        return response()->json([
            'message' => 'Reschedule has been unlocked. Client can now reschedule.',
            'reservation' => $reservation->only(['id', 'reservation_code', 'reschedule_locked', 'reschedule_locked_at']),
        ]);
    }

    /**
     * Get reschedule lock status — only the reservation owner or staff/admin may view.
     * C2 fix: added ownership + role authorization.
     */
    public function status(Request $request, Reservation $reservation): JsonResponse
    {
        $user = $request->user();

        $isStaffOrAdmin = $user->hasAnyRole(['super_admin', 'admin', 'staff', 'location_manager']);
        $isOwner = (int) $reservation->user_id === (int) $user->id;

        abort_unless($isStaffOrAdmin || $isOwner, 403, 'Unauthorized action.');

        return response()->json([
            'reservation_code' => $reservation->reservation_code,
            'reschedule_locked' => $reservation->reschedule_locked,
            'reschedule_locked_by' => $reservation->rescheduleLockedBy?->email,
            'reschedule_locked_at' => $reservation->reschedule_locked_at,
        ]);
    }

    /**
     * Reschedule a reservation.
     * C1 fix: correct overlap query + DB transaction with lockForUpdate.
     */
    public function reschedule(Request $request, Reservation $reservation): JsonResponse
    {
        $user = $request->user();

        // Authorization: owner or staff/admin
        $isStaffOrAdmin = $user->hasAnyRole(['super_admin', 'admin', 'staff', 'location_manager']);
        $isOwner = (int) $reservation->user_id === (int) $user->id;

        abort_unless($isStaffOrAdmin || $isOwner, 403, 'Unauthorized action.');

        // Clients are blocked by the lock; staff/admin bypass it
        if ($reservation->reschedule_locked && ! $isStaffOrAdmin) {
            throw ValidationException::withMessages([
                'reschedule' => 'Rescheduling is currently locked for this reservation.',
            ]);
        }

        $validated = $request->validate([
            'reservation_date' => ['required', 'date', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
        ]);

        $newDate = $validated['reservation_date'];
        $newStart = $validated['start_time'];
        $newEnd = $validated['end_time'];

        $oldSchedule = [
            'date' => $reservation->reservation_date,
            'start_time' => $reservation->start_time,
            'end_time' => $reservation->end_time,
        ];

        try {
            DB::transaction(function () use ($reservation, $newDate, $newStart, $newEnd) {
                // Lock all reservations for this court+date to prevent race conditions
                DB::table('reservations')
                    ->where('court_id', $reservation->court_id)
                    ->where('reservation_date', $newDate)
                    ->whereNull('deleted_at')
                    ->whereNotIn('status', ['cancelled', 'no_show', 'refunded'])
                    ->lockForUpdate()
                    ->get(['id']);

                // Correct overlap check: existing booking overlaps if it starts before our end AND ends after our start
                $conflict = DB::table('reservations')
                    ->where('court_id', $reservation->court_id)
                    ->where('reservation_date', $newDate)
                    ->where('id', '!=', $reservation->id)
                    ->whereNull('deleted_at')
                    ->whereNotIn('status', ['cancelled', 'no_show', 'refunded'])
                    ->where('start_time', '<', $newEnd)
                    ->where('end_time', '>', $newStart)
                    ->exists();

                if ($conflict) {
                    throw ValidationException::withMessages([
                        'schedule' => 'The selected time slot is not available.',
                    ]);
                }

                // Also check maintenance windows
                $maintenance = DB::table('court_maintenance')
                    ->where('court_id', $reservation->court_id)
                    ->where(function ($q) use ($newDate) {
                        $q->where('is_all_day', true)
                            ->whereDate('start_datetime', '<=', $newDate)
                            ->whereDate('end_datetime', '>=', $newDate);
                    })
                    ->orWhere(function ($q) use ($newDate, $newStart, $newEnd) {
                        $q->where('court_id', $reservation->court_id)
                            ->where('start_datetime', '<', $newDate.' '.$newEnd)
                            ->where('end_datetime', '>', $newDate.' '.$newStart);
                    })
                    ->exists();

                if ($maintenance) {
                    throw ValidationException::withMessages([
                        'schedule' => 'The selected time overlaps with scheduled maintenance.',
                    ]);
                }

                $reservation->update([
                    'reservation_date' => $newDate,
                    'start_time' => $newStart,
                    'end_time' => $newEnd,
                ]);
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw ValidationException::withMessages([
                'schedule' => 'Could not complete reschedule. Please try again.',
            ]);
        }

        $this->audit->log('reservation.rescheduled', 'reservations', $reservation->id, $user, [
            'reservation_code' => $reservation->reservation_code,
            'old_schedule' => $oldSchedule,
            'new_schedule' => ['date' => $newDate, 'start_time' => $newStart, 'end_time' => $newEnd],
        ]);

        if (! $isStaffOrAdmin) {
            app(NotificationService::class)->notifyStaffAndAdmins(
                'Reservation Rescheduled',
                "Client rescheduled booking {$reservation->reservation_code} to {$newDate} at {$newStart} - {$newEnd}.",
                $reservation
            );
        }

        return response()->json([
            'message' => 'Reservation rescheduled successfully.',
            'reservation' => [
                'id' => $reservation->id,
                'reservation_code' => $reservation->reservation_code,
                'old_schedule' => $oldSchedule,
                'new_schedule' => [
                    'date' => $newDate,
                    'start_time' => $newStart,
                    'end_time' => $newEnd,
                ],
            ],
        ]);
    }
}
