<?php

namespace App\Http\Controllers;

use App\Models\Court;
use App\Models\CourtPricingRule;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CourtController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function update(Request $request, Court $court): RedirectResponse
    {
        $this->authorizeManage($request);

        $validated = $request->validate([
            'court_number' => ['required', 'string', 'max:50'],
            'court_name' => ['nullable', 'string', 'max:200'],
            'court_type' => ['required', 'in:indoor,outdoor,covered'],
            'surface_type' => ['required', 'in:concrete,asphalt,acrylic,grass,clay'],
            'base_price' => ['required', 'numeric', 'min:0', 'max:99999'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $duplicate = Court::query()
            ->where('location_id', $court->location_id)
            ->where('court_number', $validated['court_number'])
            ->where('id', '<>', $court->id)
            ->exists();

        if ($duplicate) {
            return back()->withInput()->with('error', 'Another court with that number exists at this location.');
        }

        $court->update([
            'court_number' => $validated['court_number'],
            'court_name' => $validated['court_name'],
            'court_type' => $validated['court_type'],
            'surface_type' => $validated['surface_type'],
            'is_active' => $request->boolean('is_active', $court->is_active),
            'has_shade' => $validated['court_type'] !== 'outdoor',
            'is_airconditioned' => $validated['court_type'] === 'indoor',
        ]);

        // Update default pricing rule if it exists
        $primaryRule = CourtPricingRule::query()
            ->where('court_id', $court->id)
            ->orderBy('priority')
            ->first();

        if ($primaryRule) {
            $primaryRule->update(['base_price' => $validated['base_price']]);
        }

        $this->audit->log('court.updated', 'courts', $court->id, $request->user(), $validated);

        return back()->with('status', 'Court '.$court->court_number.' updated.');
    }

    public function destroy(Request $request, Court $court): RedirectResponse
    {
        $this->authorizeManage($request);

        $hasFutureBookings = DB::table('reservations')
            ->where('court_id', $court->id)
            ->whereNull('deleted_at')
            ->where('reservation_date', '>=', now()->toDateString())
            ->whereNotIn('status', ['cancelled', 'no_show', 'refunded', 'completed'])
            ->exists();

        if ($hasFutureBookings) {
            return back()->with('error', 'Court has upcoming bookings. Cancel or move them before archiving.');
        }

        $court->update(['is_active' => false]);
        $court->delete();

        $this->audit->log('court.archived', 'courts', $court->id, $request->user(), [
            'court_number' => $court->court_number,
        ]);

        return back()->with('status', 'Court archived.');
    }

    public function toggle(Request $request, Court $court): RedirectResponse
    {
        $this->authorizeManage($request);

        // I7 fix: warn if deactivating a court with future confirmed bookings
        if ($court->is_active) {
            $hasFutureBookings = DB::table('reservations')
                ->where('court_id', $court->id)
                ->whereNull('deleted_at')
                ->where('reservation_date', '>=', now()->toDateString())
                ->whereNotIn('status', ['cancelled', 'no_show', 'refunded', 'completed'])
                ->exists();

            if ($hasFutureBookings) {
                return back()->with('error', 'Court has upcoming bookings. Cancel or move them before deactivating.');
            }
        }

        $court->update(['is_active' => ! $court->is_active]);

        $this->audit->log('court.toggled', 'courts', $court->id, $request->user(), [
            'is_active' => $court->is_active,
        ]);

        return back()->with('status', 'Court '.$court->court_number.' is now '.($court->is_active ? 'active' : 'hidden').'.');
    }

    private function authorizeManage(Request $request): void
    {
        abort_unless($request->user()->hasAnyRole([User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN]), 403);
    }

    public function getRates(Court $court): JsonResponse
    {
        $rates = DB::table('court_pricing_rules')
            ->where('court_id', $court->id)
            ->where('is_active', true)
            ->orderBy('priority')
            ->get();

        return response()->json($rates);
    }

    public function addRate(Request $request, Court $court): JsonResponse
    {
        $this->authorizeManage($request);

        $validated = $request->validate([
            'day_of_week' => ['nullable', 'integer', 'min:0', 'max:6'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'different:start_time'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'effective_to' => ['nullable', 'date'],
        ]);

        $id = DB::table('court_pricing_rules')->insertGetId([
            'court_id' => $court->id,
            'rule_name' => 'Custom Rate',
            'start_time' => $validated['start_time'].':00',
            'end_time' => $validated['end_time'].':00',
            'base_price' => $validated['base_price'],
            'day_of_week' => $validated['day_of_week'],
            'is_holiday' => false,
            'is_peak_season' => false,
            'priority' => 10,
            'is_active' => true,
            'effective_from' => now()->toDateString(),
            'effective_to' => $validated['effective_to'],
            'created_by' => $request->user()->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->audit->log('court.rate.added', 'courts', $court->id, $request->user(), $validated);

        return response()->json(['success' => true, 'id' => $id]);
    }

    public function updateRate(Request $request, $rateId): JsonResponse
    {
        $this->authorizeManage($request);

        $validated = $request->validate([
            'day_of_week' => ['nullable', 'integer', 'min:0', 'max:6'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'different:start_time'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'effective_to' => ['nullable', 'date'],
        ]);

        $rule = DB::table('court_pricing_rules')->where('id', $rateId)->first();
        if (!$rule) {
            return response()->json(['error' => 'Rate rule not found'], 404);
        }

        if (is_null($rule->start_time)) {
            return response()->json(['error' => 'Cannot edit the standard baseline rate rule directly.'], 400);
        }

        DB::table('court_pricing_rules')
            ->where('id', $rateId)
            ->update([
                'day_of_week' => $validated['day_of_week'],
                'start_time' => $validated['start_time'].':00',
                'end_time' => $validated['end_time'].':00',
                'base_price' => $validated['base_price'],
                'effective_to' => $validated['effective_to'],
                'updated_at' => now(),
            ]);

        $this->audit->log('court.rate.updated', 'courts', $rule->court_id, $request->user(), $validated);

        return response()->json(['success' => true]);
    }

    public function deleteRate(Request $request, $rateId): JsonResponse
    {
        $this->authorizeManage($request);

        $rule = DB::table('court_pricing_rules')->where('id', $rateId)->first();
        if (!$rule) {
            return response()->json(['error' => 'Rate rule not found'], 404);
        }

        if (is_null($rule->start_time)) {
            return response()->json(['error' => 'Cannot delete the standard baseline rate rule.'], 400);
        }

        DB::table('court_pricing_rules')->where('id', $rateId)->delete();

        $this->audit->log('court.rate.deleted', 'courts', $rule->court_id, $request->user(), [
            'rule_name' => $rule->rule_name,
            'start_time' => $rule->start_time,
            'end_time' => $rule->end_time,
        ]);

        return response()->json(['success' => true]);
    }
}
