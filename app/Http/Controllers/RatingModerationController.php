<?php

namespace App\Http\Controllers;

use App\Models\Rating;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RatingModerationController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function respond(Request $request, Rating $rating): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $validated = $request->validate([
            'admin_response' => ['required', 'string', 'max:2000'],
        ]);

        $rating->update([
            'admin_response' => $validated['admin_response'],
            'admin_responded_by' => $request->user()->id,
            'admin_responded_at' => now(),
            'status' => 'approved',
        ]);

        $this->audit->log('rating.responded', 'ratings', $rating->id, $request->user(), $validated);

        return back()->with('status', 'Response posted on the review.');
    }

    public function hide(Request $request, Rating $rating): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $rating->update(['status' => 'hidden']);

        $this->audit->log('rating.hidden', 'ratings', $rating->id, $request->user(), [
            'rating_score' => $rating->rating_score,
        ]);

        return back()->with('status', 'Review hidden from public listings.');
    }

    public function approve(Request $request, Rating $rating): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $rating->update(['status' => 'approved']);

        $this->audit->log('rating.approved', 'ratings', $rating->id, $request->user());

        return back()->with('status', 'Review approved.');
    }

    public function adjust(Request $request, Rating $rating): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $validated = $request->validate([
            'admin_adjusted_score' => ['required', 'integer', 'between:1,5'],
            'admin_adjustment_reason' => ['required', 'string', 'max:1000'],
        ]);

        $original = $rating->original_rating_score ?? $rating->rating_score;

        $rating->update([
            'original_rating_score' => $original,
            'admin_adjusted_score' => $validated['admin_adjusted_score'],
            'admin_adjustment_reason' => $validated['admin_adjustment_reason'],
            'admin_adjusted_by' => $request->user()->id,
            'admin_adjusted_at' => now(),
            'rating_score' => $validated['admin_adjusted_score'],
        ]);

        $this->audit->log('rating.adjusted', 'ratings', $rating->id, $request->user(), [
            'from' => $original,
            'to' => $validated['admin_adjusted_score'],
            'reason' => $validated['admin_adjustment_reason'],
        ]);

        return back()->with('status', 'Rating score adjusted with audit log entry.');
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless(
            $request->user()->hasAnyRole([User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN]),
            403,
        );
    }
}
