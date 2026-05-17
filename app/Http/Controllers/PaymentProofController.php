<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\StaffProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentProofController extends Controller
{
    /**
     * Stream a private GCash screenshot with strict authorization.
     * Customers can see their own; admins can see all; location staff can see
     * only payments belonging to their assigned branch.
     */
    public function show(Request $request, Payment $payment): StreamedResponse
    {
        $user = $request->user();
        $payment->loadMissing('reservation');

        abort_unless($payment->gcash_screenshot_path, 404);

        $isAdmin = $user->hasAnyRole([User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN]);
        $isStaff = $user->hasAnyRole([User::ROLE_LOCATION_MANAGER, User::ROLE_STAFF]);
        $isOwner = $payment->reservation && $payment->reservation->user_id === $user->id;

        if (! $isAdmin && ! $isOwner) {
            if ($isStaff) {
                $locationId = StaffProfile::query()->where('user_id', $user->id)->value('assigned_location_id');
                abort_if($locationId && (int) $payment->reservation->location_id !== (int) $locationId, 403);
            } else {
                abort(403);
            }
        }

        abort_unless(Storage::disk('local')->exists($payment->gcash_screenshot_path), 404);

        return Storage::disk('local')->download(
            $payment->gcash_screenshot_path,
            'payment-proof-'.$payment->payment_reference.'.'.pathinfo($payment->gcash_screenshot_path, PATHINFO_EXTENSION),
        );
    }
}
