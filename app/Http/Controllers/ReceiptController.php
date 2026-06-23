<?php

namespace App\Http\Controllers;

use App\Models\EndUserProfile;
use App\Models\Reservation;
use App\Models\StaffProfile;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReceiptController extends Controller
{
    public function show(Request $request, Reservation $reservation): View
    {
        $user = $request->user();

        if (! $user->hasAnyRole([User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN])) {
            if ($user->hasAnyRole([User::ROLE_LOCATION_MANAGER, User::ROLE_STAFF])) {
                $locationId = StaffProfile::query()->where('user_id', $user->id)->value('assigned_location_id');
                abort_if($locationId && (int) $reservation->location_id !== (int) $locationId, 403);
            } else {
                abort_if($reservation->user_id !== $user->id, 403);
            }
        }

        $reservation->load([
            'user',
            'court.location',
            'location',
            'equipment.equipmentType',
            'payments' => fn ($q) => $q->orderBy('id'),
        ]);

        return view('receipts.show', [
            'reservation' => $reservation,
            'profile' => EndUserProfile::query()->where('user_id', $reservation->user_id)->first(),
        ]);
    }

    public function confirm(Request $request, Reservation $reservation): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user->hasAnyRole([User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN, User::ROLE_LOCATION_MANAGER, User::ROLE_STAFF]), 403);

        if (! $user->hasAnyRole([User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN])) {
            $locationId = StaffProfile::query()->where('user_id', $user->id)->value('assigned_location_id');
            abort_if($locationId && (int) $reservation->location_id !== (int) $locationId, 403);
        }

        DB::transaction(function () use ($reservation, $user) {
            DB::table('reservations')->where('id', $reservation->id)->update([
                'status' => 'confirmed',
                'payment_status' => 'paid',
                'confirmed_at' => now(),
                'confirmed_by' => $user->id,
                'updated_at' => now(),
            ]);

            DB::table('payments')
                ->where('reservation_id', $reservation->id)
                ->whereIn('status', ['pending', 'payment_verification', 'pending_verification'])
                ->update([
                    'status' => 'verified',
                    'verified_by' => $user->id,
                    'verified_at' => now(),
                    'updated_at' => now(),
                ]);
        });

        return redirect()->back()->with('success', 'Booking '.$reservation->reservation_code.' confirmed.');
    }

    public function destroy(Request $request, Reservation $reservation): RedirectResponse
    {
        abort_if(SystemSetting::value('enable_book_history_deletion', 'true') !== 'true', 403, 'Deletion is disabled by the admin.');

        $user = $request->user();

        if (! $user->hasAnyRole([User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN])) {
            if ($user->hasAnyRole([User::ROLE_LOCATION_MANAGER, User::ROLE_STAFF])) {
                $locationId = StaffProfile::query()->where('user_id', $user->id)->value('assigned_location_id');
                abort_if($locationId && (int) $reservation->location_id !== (int) $locationId, 403);
            } else {
                abort_if($reservation->user_id !== $user->id, 403);
            }
        }

        $reservation->delete();

        return redirect()->back()->with('success', 'Booking deleted successfully.');
    }

    public function bulkDelete(Request $request): RedirectResponse
    {
        abort_if(SystemSetting::value('enable_book_history_deletion', 'true') !== 'true', 403, 'Deletion is disabled by the admin.');

        $user = $request->user();

        if (! $user->hasAnyRole([User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN])) {
            abort_unless($user->hasAnyRole([User::ROLE_LOCATION_MANAGER, User::ROLE_STAFF]), 403);
        }

        $filter = $request->input('filter');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $query = DB::table('reservations as r')
            ->whereNull('r.deleted_at');

        if (! $user->hasAnyRole([User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN])) {
            $locationId = StaffProfile::query()->where('user_id', $user->id)->value('assigned_location_id');
            if ($locationId) {
                $query->where('r.location_id', '=', $locationId);
            } else {
                $query->where('r.user_id', '=', $user->id);
            }
        }

        if ($filter) {
            if ($filter === 'today') {
                $query->where('r.reservation_date', '=', now()->toDateString());
            } elseif ($filter === 'yesterday') {
                $query->where('r.reservation_date', '=', now()->subDay()->toDateString());
            } elseif ($filter === 'last_week') {
                $query->whereBetween('r.reservation_date', [
                    now()->subDays(6)->toDateString(),
                    now()->toDateString(),
                ]);
            } elseif ($filter === 'month') {
                $query->whereBetween('r.reservation_date', [
                    now()->subDays(29)->toDateString(),
                    now()->toDateString(),
                ]);
            } elseif ($filter === 'last_month') {
                $query->whereBetween('r.reservation_date', [
                    now()->subMonth()->startOfMonth()->toDateString(),
                    now()->subMonth()->endOfMonth()->toDateString(),
                ]);
            } elseif ($filter === 'custom') {
                if ($startDate && $endDate) {
                    $query->whereBetween('r.reservation_date', [$startDate, $endDate]);
                } elseif ($startDate) {
                    $query->where('r.reservation_date', '>=', $startDate);
                } elseif ($endDate) {
                    $query->where('r.reservation_date', '<=', $endDate);
                }
            }
        }

        $ids = $query->pluck('r.id')->toArray();
        if (! empty($ids)) {
            DB::table('reservations')
                ->whereIn('id', $ids)
                ->update(['deleted_at' => now(), 'updated_at' => now()]);
        }

        return redirect()->back()->with('success', 'Filtered bookings deleted successfully.');
    }
}
