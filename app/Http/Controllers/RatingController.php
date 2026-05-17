<?php

namespace App\Http\Controllers;

use App\Models\Rating;
use App\Models\Reservation;
use App\Services\AuditService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RatingController extends Controller
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly NotificationService $notifications,
    ) {
    }

    public function store(Request $request, Reservation $reservation): RedirectResponse
    {
        $user = $request->user();
        abort_if($reservation->user_id !== $user->id, 403);
        abort_if($reservation->status !== 'completed', 403, 'You can only rate completed reservations.');

        if ($reservation->rating()->exists()) {
            return back()->with('error', 'You already rated this reservation.');
        }

        $validated = $request->validate([
            'rating_score' => ['required', 'integer', 'between:1,5'],
            'review_title' => ['nullable', 'string', 'max:200'],
            'review_comment' => ['nullable', 'string', 'max:2000'],
            'would_recommend' => ['nullable', 'boolean'],
            'cleanliness' => ['nullable', 'integer', 'between:1,5'],
            'facilities' => ['nullable', 'integer', 'between:1,5'],
            'staff' => ['nullable', 'integer', 'between:1,5'],
            'value' => ['nullable', 'integer', 'between:1,5'],
        ]);

        $categories = array_filter([
            'cleanliness' => $validated['cleanliness'] ?? null,
            'facilities' => $validated['facilities'] ?? null,
            'staff' => $validated['staff'] ?? null,
            'value' => $validated['value'] ?? null,
        ], fn ($v) => $v !== null);

        Rating::query()->create([
            'reservation_id' => $reservation->id,
            'user_id' => $user->id,
            'court_id' => $reservation->court_id,
            'rating_score' => $validated['rating_score'],
            'review_title' => $validated['review_title'] ?? null,
            'review_comment' => $validated['review_comment'] ?? null,
            'categories' => $categories ?: null,
            'would_recommend' => $request->boolean('would_recommend'),
            'is_verified_purchase' => true,
            'status' => 'pending',
        ]);

        $this->audit->log('rating.submitted', 'ratings', $reservation->id, $user, $validated);
        $this->notifications->notifyAdmins(
            'New review for '.$reservation->reservation_code,
            $validated['rating_score'].'/5 stars submitted.',
            $reservation,
            ['rating' => $validated['rating_score']],
        );

        return back()->with('status', 'Review submitted. Thanks for the feedback.');
    }
}
