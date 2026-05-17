<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\StaffProfile;
use App\Models\User;
use Illuminate\Http\Request;
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
            'payments' => fn ($q) => $q->whereIn('status', ['verified', 'refunded'])->orderBy('id'),
        ]);

        return view('receipts.show', [
            'reservation' => $reservation,
            'profile' => \App\Models\EndUserProfile::query()->where('user_id', $reservation->user_id)->first(),
        ]);
    }
}
