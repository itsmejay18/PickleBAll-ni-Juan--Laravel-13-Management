<?php

namespace App\Http\Controllers;

use App\Models\Court;
use App\Models\EndUserProfile;
use App\Models\OpenPlayEvent;
use App\Models\OpenPlayMatch;
use App\Models\OpenPlayRegistration;
use App\Models\OpenPlayRound;
use App\Models\OpenPlaySetting;
use App\Models\StaffProfile;
use App\Models\SystemSetting;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OpenPlayController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    /* ===================== PUBLIC ===================== */

    // "My Open Play" page: upcoming event, my registrations, my assigned matches.
    public function index(Request $request): View
    {
        $user = $request->user();
        $settings = OpenPlaySetting::current();
        $event = OpenPlayEvent::upcoming($settings);

        $myRegistrations = OpenPlayRegistration::query()
            ->with('event')
            ->where('user_id', $user->id)
            ->where('status', '!=', 'cancelled')
            ->orderByDesc('id')
            ->get();

        $myRegistration = $event
            ? $myRegistrations->firstWhere('open_play_event_id', $event->id)
            : null;

        $myMatches = [];
        if ($event && $event->matches_generated) {
            $myMatches = $this->matchesForUser($event, $user->id);
        }

        return view('open-play.index', [
            'settings' => $settings,
            'event' => $event,
            'myRegistration' => $myRegistration,
            'myRegistrations' => $myRegistrations,
            'myMatches' => $myMatches,
        ]);
    }

    public function join(Request $request, OpenPlayEvent $event): RedirectResponse
    {
        $user = $request->user();

        if ($event->status !== 'open') {
            return back()->with('error', 'Registration for this Open Play is closed.');
        }

        $registration = null;

        try {
            $registration = DB::transaction(function () use ($event, $user) {
                $locked = OpenPlayEvent::query()->lockForUpdate()->find($event->id);

                $existing = OpenPlayRegistration::query()
                    ->where('open_play_event_id', $locked->id)
                    ->where('user_id', $user->id)
                    ->first();

                if ($existing) {
                    if ($existing->status === 'cancelled') {
                        $initialStatus = $locked->entrance_fee > 0 ? 'pending_payment' : 'registered';
                        $existing->update([
                            'status' => $initialStatus,
                            'amount_paid' => $locked->entrance_fee > 0 ? 0 : $locked->entrance_fee,
                            'registered_at' => now(),
                        ]);

                        return $existing;
                    }

                    throw new \RuntimeException('duplicate');
                }

                $taken = OpenPlayRegistration::query()
                    ->where('open_play_event_id', $locked->id)
                    ->where('status', '!=', 'cancelled')
                    ->count();

                if ($taken >= $locked->max_slots) {
                    $locked->update(['status' => 'full']);
                    throw new \RuntimeException('full');
                }

                $initialStatus = $locked->entrance_fee > 0 ? 'pending_payment' : 'registered';
                $reg = OpenPlayRegistration::query()->create([
                    'open_play_event_id' => $locked->id,
                    'user_id' => $user->id,
                    'slot_number' => $taken + 1,
                    'status' => $initialStatus,
                    'amount_paid' => $locked->entrance_fee > 0 ? 0 : $locked->entrance_fee,
                    'registered_at' => now(),
                ]);

                if (($taken + 1) >= $locked->max_slots) {
                    $locked->update(['status' => 'full']);
                }

                return $reg;
            });
        } catch (\RuntimeException $e) {
            return match ($e->getMessage()) {
                'duplicate' => back()->with('error', 'You are already registered for this Open Play.'),
                'full' => back()->with('error', 'Open Play is already full.'),
                default => back()->with('error', 'Unable to register. Please try again.'),
            };
        }

        if ($registration->status === 'pending_payment') {
            return redirect()->route('open-play.pay', $registration->id);
        }

        return redirect()
            ->route('open-play.ticket', $registration->id)
            ->with('success', 'You have joined Open Play. Slot #'.$registration->slot_number.' reserved.');
    }

    public function ticket(Request $request, OpenPlayRegistration $registration): View
    {
        $user = $request->user();
        abort_unless($registration->user_id === $user->id || $this->isStaff($user), 403);

        $registration->load('event', 'user');

        return view('open-play.ticket', [
            'registration' => $registration,
            'event' => $registration->event,
        ]);
    }

    // Real-time slot counter endpoint.
    public function slots(OpenPlayEvent $event): JsonResponse
    {
        return response()->json([
            'event_id' => $event->id,
            'max_slots' => $event->max_slots,
            'taken' => $event->takenSlots(),
            'remaining' => $event->remainingSlots(),
            'is_full' => $event->isFull(),
            'status' => $event->isFull() ? 'full' : $event->status,
        ]);
    }

    public function payRegistration(Request $request, OpenPlayRegistration $registration): RedirectResponse
    {
        $user = $request->user();
        abort_unless((int) $registration->user_id === (int) $user->id, 403);

        if ($registration->status !== 'pending_payment') {
            return back()->with('error', 'This registration does not require payment.');
        }

        $event = $registration->event;
        $isEnabled = filter_var(SystemSetting::value('xpaylink_enabled', 'false'), FILTER_VALIDATE_BOOLEAN);
        $publicKey = SystemSetting::value('xpaylink_public_key');
        $secretKey = SystemSetting::value('xpaylink_secret_key');
        $endpoint = SystemSetting::value('xpaylink_endpoint', 'https://synthwave.space/api/create-session.php');

        if (! $isEnabled || empty($publicKey) || empty($secretKey)) {
            return back()->with('error', 'Automatic payments are not configured properly. Please contact the administrator.');
        }

        $profile = $user->endUserProfile ?? $user->staffProfile ?? $user->adminProfile;
        $customerName = trim(($profile?->first_name ?? '').' '.($profile?->last_name ?? '')) ?: $user->email;

        $payload = [
            'external_bill_id' => 'OPREG-' . $registration->id,
            'customer_name' => $customerName,
            'amount' => (float) $event->entrance_fee,
            'callback_url' => route('payments.xpaylink.webhook'),
            'success_url' => route('open-play.index'),
            'return_url' => route('open-play.index'),
            'failed_url' => route('open-play.index'),
        ];

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-PayLink-Key' => $publicKey,
                'X-PayLink-Secret' => $secretKey,
            ])->post($endpoint, $payload);

            if ($response->successful() && $response->json('success') === true) {
                $paymentUrl = $response->json('payment_url');
                return redirect()->away($paymentUrl);
            }

            $errorMessage = $response->json('message') ?? 'Could not create a payment session.';
            return back()->with('error', 'Payment Gateway Error: ' . $errorMessage);

        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'Unable to reach the payment gateway.');
        }
    }

    public function registrationStatus(Request $request, OpenPlayRegistration $registration): JsonResponse
    {
        $user = $request->user();
        abort_unless((int) $registration->user_id === (int) $user->id || $user->hasAnyRole([User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN, User::ROLE_STAFF]), 403);

        return response()->json([
            'id' => $registration->id,
            'status' => $registration->status,
        ]);
    }

    /* ===================== ADMIN / STAFF ===================== */

    public function manage(Request $request): View
    {
        $user = $request->user();
        $this->ensureStaff($user);

        $settings = OpenPlaySetting::current();
        $event = OpenPlayEvent::upcoming($settings);

        $search = trim((string) $request->query('q', ''));

        $participants = collect();
        if ($event) {
            $participants = OpenPlayRegistration::query()
                ->where('open_play_event_id', $event->id)
                ->where('status', '!=', 'cancelled')
                ->orderBy('slot_number')
                ->get();

            $names = $this->playerNames($participants->pluck('user_id')->all());
            $emails = $this->playerEmails($participants->pluck('user_id')->all());

            $participants = $participants->map(function ($p) use ($names, $emails) {
                $p->display_name = $names[$p->user_id] ?? ('User #'.$p->user_id);
                $p->email = $emails[$p->user_id] ?? '';

                return $p;
            });

            if ($search !== '') {
                $needle = strtolower($search);
                $participants = $participants->filter(fn ($p) => str_contains(strtolower($p->display_name), $needle)
                    || str_contains(strtolower($p->email), $needle)
                    || str_contains((string) $p->slot_number, $needle))->values();
            }
        }

        $rounds = [];
        if ($event && $event->matches_generated) {
            $rounds = $this->roundsView($event);
        }
        $userSearch = trim((string) $request->query('user_q', ''));

        $unregisteredUsers = collect();
        if ($event) {
            $registeredUserIds = OpenPlayRegistration::query()
                ->where('open_play_event_id', $event->id)
                ->where('status', '!=', 'cancelled')
                ->pluck('user_id')
                ->all();

            $userQuery = User::query()
                ->whereNotIn('id', $registeredUserIds)
                ->with('endUserProfile');

            if ($userSearch !== '') {
                $userQuery->where(function ($q) use ($userSearch) {
                    $q->where('email', 'like', "%{$userSearch}%")
                      ->orWhereHas('endUserProfile', function ($pq) use ($userSearch) {
                          $pq->where('first_name', 'like', "%{$userSearch}%")
                            ->orWhere('last_name', 'like', "%{$userSearch}%");
                      });
                });
            }

            $unregisteredUsers = $userQuery->limit(50)->get()
                ->map(function ($u) {
                    $name = $u->endUserProfile 
                        ? trim(($u->endUserProfile->first_name ?? '').' '.($u->endUserProfile->last_name ?? '')) 
                        : '';
                    $u->display_label = $name !== '' ? "$name ({$u->email})" : $u->email;
                    return $u;
                })
                ->sortBy('display_label');
        }

        return view('open-play.manage', [
            'settings' => $settings,
            'event' => $event,
            'participants' => $participants,
            'rounds' => $rounds,
            'search' => $search,
            'userSearch' => $userSearch,
            'locations' => DB::table('locations')->where('is_active', true)->whereNull('deleted_at')->orderBy('name')->get(['id', 'name']),
            'unregisteredUsers' => $unregisteredUsers,
        ]);
    }

    public function updateSettings(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->ensureStaff($user);

        $validated = $request->validate([
            'is_enabled' => ['nullable'],
            'day_of_week' => ['required', 'integer', 'between:0,6'],
            'open_play_start' => ['required', 'date_format:H:i'],
            'open_play_end' => ['required', 'date_format:H:i'],
            'attendance_closing_time' => ['required', 'date_format:H:i'],
            'booking_open_start' => ['required', 'date_format:H:i'],
            'booking_open_end' => ['required', 'date_format:H:i'],
            'booking_resume_start' => ['required', 'date_format:H:i'],
            'booking_resume_end' => ['required', 'date_format:H:i'],
            'entrance_fee' => ['required', 'numeric', 'min:0', 'max:100000'],
            'max_slots' => ['required', 'integer', 'min:4', 'max:500'],
            'rounds' => ['required', 'integer', 'min:1', 'max:12'],
            'location_id' => ['nullable', 'integer', 'exists:locations,id'],
        ]);

        $settings = OpenPlaySetting::current();
        $settings->update([
            'is_enabled' => $request->boolean('is_enabled'),
            'day_of_week' => $validated['day_of_week'],
            'open_play_start' => $validated['open_play_start'],
            'open_play_end' => $validated['open_play_end'],
            'attendance_closing_time' => $validated['attendance_closing_time'],
            'booking_open_start' => $validated['booking_open_start'],
            'booking_open_end' => $validated['booking_open_end'],
            'booking_resume_start' => $validated['booking_resume_start'],
            'booking_resume_end' => $validated['booking_resume_end'],
            'entrance_fee' => $validated['entrance_fee'],
            'max_slots' => $validated['max_slots'],
            'rounds' => $validated['rounds'],
            'location_id' => $validated['location_id'] ?? null,
        ]);

        // Refresh / create the upcoming event from the new settings.
        OpenPlayEvent::upcoming($settings->fresh());

        $this->audit->log('open_play.settings.updated', 'open_play_settings', $settings->id, $user, $validated);

        return redirect()->route('open-play.manage')->with('success', 'Open Play settings saved.');
    }

    public function generate(Request $request, OpenPlayEvent $event): RedirectResponse
    {
        $user = $request->user();
        $this->ensureStaff($user);

        $count = $this->buildMatches($event, 1);

        if ($count === 0) {
            return back()->with('error', 'Need at least 4 checked-in players to generate matches.');
        }

        $event->update(['matches_generated' => true, 'status' => 'closed']);
        $this->audit->log('open_play.matches.generated', 'open_play_events', $event->id, $user, ['matches' => $count]);

        return back()->with('success', 'Generated Round 1 matches.');
    }

    public function regenerate(Request $request, OpenPlayEvent $event): RedirectResponse
    {
        $user = $request->user();
        $this->ensureStaff($user);

        // Find the latest round
        $latestRound = OpenPlayRound::query()
            ->where('open_play_event_id', $event->id)
            ->orderByDesc('round_number')
            ->first();

        $roundNumber = $latestRound ? $latestRound->round_number : 1;

        $count = $this->buildMatches($event, $roundNumber);

        if ($count === 0) {
            return back()->with('error', 'Need at least 4 checked-in players to regenerate matches.');
        }

        $event->update(['matches_generated' => true]);
        $this->audit->log('open_play.matches.regenerated', 'open_play_events', $event->id, $user, ['matches' => $count]);

        return back()->with('success', 'Round '.$roundNumber.' matches regenerated.');
    }

    public function checkIn(Request $request, OpenPlayRegistration $registration): RedirectResponse
    {
        $user = $request->user();
        $this->ensureStaff($user);

        if ($registration->status === 'checked_in') {
            $registration->update(['status' => 'registered', 'checked_in_at' => null]);

            return back()->with('success', 'Check-in undone.');
        }

        $registration->update(['status' => 'checked_in', 'checked_in_at' => now()]);
        $this->audit->log('open_play.checked_in', 'open_play_registrations', $registration->id, $user, []);

        return back()->with('success', 'Participant checked in.');
    }

    public function registerCash(Request $request, OpenPlayEvent $event): RedirectResponse
    {
        $user = $request->user();
        $this->ensureStaff($user);

        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $targetUserId = (int) $validated['user_id'];

        try {
            DB::transaction(function () use ($event, $targetUserId) {
                $locked = OpenPlayEvent::query()->lockForUpdate()->find($event->id);

                $existing = OpenPlayRegistration::query()
                    ->where('open_play_event_id', $locked->id)
                    ->where('user_id', $targetUserId)
                    ->first();

                if ($existing) {
                    if ($existing->status === 'cancelled' || $existing->status === 'pending_payment') {
                        $existing->update([
                            'status' => 'registered',
                            'amount_paid' => $locked->entrance_fee,
                            'registered_at' => now(),
                        ]);
                        return;
                    }
                    throw new \RuntimeException('already_registered');
                }

                $taken = OpenPlayRegistration::query()
                    ->where('open_play_event_id', $locked->id)
                    ->where('status', '!=', 'cancelled')
                    ->count();

                if ($taken >= $locked->max_slots) {
                    $locked->update(['status' => 'full']);
                    throw new \RuntimeException('full');
                }

                OpenPlayRegistration::query()->create([
                    'open_play_event_id' => $locked->id,
                    'user_id' => $targetUserId,
                    'slot_number' => $taken + 1,
                    'status' => 'registered',
                    'amount_paid' => $locked->entrance_fee,
                    'registered_at' => now(),
                ]);

                if (($taken + 1) >= $locked->max_slots) {
                    $locked->update(['status' => 'full']);
                }
            });
        } catch (\RuntimeException $e) {
            return match ($e->getMessage()) {
                'already_registered' => back()->with('error', 'Player is already registered.'),
                'full' => back()->with('error', 'Open Play is full.'),
                default => back()->with('error', 'Error registering player.'),
            };
        }

        return back()->with('success', 'Player registered successfully via Cash payment.');
    }

    public function markCashPaid(Request $request, OpenPlayRegistration $registration): RedirectResponse
    {
        $user = $request->user();
        $this->ensureStaff($user);

        if ($registration->status !== 'pending_payment') {
            return back()->with('error', 'Registration is not pending payment.');
        }

        $registration->update([
            'status' => 'registered',
            'amount_paid' => $registration->event->entrance_fee,
            'registered_at' => now(),
        ]);

        $this->audit->log('open_play.cash_payment_received', 'open_play_registrations', $registration->id, $user, []);

        return back()->with('success', 'Cash payment recorded. Registration confirmed.');
    }

    public function removeParticipant(Request $request, OpenPlayRegistration $registration): RedirectResponse
    {
        $user = $request->user();
        $this->ensureStaff($user);

        $eventId = $registration->open_play_event_id;
        $registration->update(['status' => 'cancelled']);

        // Re-open registration if it was full.
        $event = OpenPlayEvent::find($eventId);
        if ($event && $event->status === 'full' && ! $event->isFull()) {
            $event->update(['status' => 'open']);
        }

        $this->audit->log('open_play.participant.removed', 'open_play_registrations', $registration->id, $user, []);

        return back()->with('success', 'Participant removed.');
    }

    public function export(Request $request, OpenPlayEvent $event): StreamedResponse
    {
        $user = $request->user();
        $this->ensureStaff($user);

        $participants = OpenPlayRegistration::query()
            ->where('open_play_event_id', $event->id)
            ->where('status', '!=', 'cancelled')
            ->orderBy('slot_number')
            ->get();

        $names = $this->playerNames($participants->pluck('user_id')->all());
        $emails = $this->playerEmails($participants->pluck('user_id')->all());

        $filename = 'open-play-'.$event->event_date->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($participants, $names, $emails) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Slot', 'Name', 'Email', 'Status', 'Checked In At', 'Amount Paid', 'Registered At']);
            foreach ($participants as $p) {
                fputcsv($out, [
                    $p->slot_number,
                    $names[$p->user_id] ?? ('User #'.$p->user_id),
                    $emails[$p->user_id] ?? '',
                    ucfirst(str_replace('_', ' ', $p->status)),
                    $p->checked_in_at?->format('Y-m-d H:i') ?? '',
                    number_format((float) $p->amount_paid, 2),
                    $p->registered_at?->format('Y-m-d H:i') ?? '',
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    public function selfCheckIn(Request $request, OpenPlayEvent $event): RedirectResponse
    {
        $user = $request->user();

        $registration = OpenPlayRegistration::query()
            ->where('open_play_event_id', $event->id)
            ->where('user_id', $user->id)
            ->where('status', '!=', 'cancelled')
            ->first();

        if (! $registration) {
            return back()->with('error', 'You are not registered for this Open Play event.');
        }

        if ($event->isAttendanceLocked()) {
            return back()->with('error', 'Attendance is locked. The self check-in deadline has passed.');
        }

        if ($registration->status === 'checked_in') {
            return back()->with('info', 'You are already checked in as Present.');
        }

        $registration->update([
            'status' => 'checked_in',
            'checked_in_at' => now(),
        ]);

        $this->audit->log('open_play.self_checked_in', 'open_play_registrations', $registration->id, $user, []);

        return back()->with('success', 'You have successfully checked in as Present.');
    }

    public function submitMatchResult(Request $request, OpenPlayMatch $match): RedirectResponse
    {
        $user = $request->user();
        $this->ensureStaff($user);

        $validated = $request->validate([
            'winner_team' => ['required', 'integer', 'in:1,2'],
        ]);

        $winnerTeam = (int) $validated['winner_team'];
        $oldWinner = $match->winner_team;

        // If a result was already submitted, revert the old rating changes first
        if ($oldWinner !== null && $oldWinner !== $winnerTeam) {
            $this->applyRatingChanges($match, $oldWinner, true);
        }

        if ($oldWinner === null || $oldWinner !== $winnerTeam) {
            $match->update(['winner_team' => $winnerTeam]);
            $this->applyRatingChanges($match, $winnerTeam, false);

            $this->audit->log('open_play.match.result_submitted', 'open_play_matches', $match->id, $user, [
                'winner_team' => $winnerTeam,
            ]);
        }

        // Check if all matches in the current round are completed.
        $round = $match->round;
        $event = $match->event;
        $allMatchesInRound = OpenPlayMatch::where('open_play_round_id', $round->id)->get();
        $allCompleted = $allMatchesInRound->every(fn ($m) => $m->winner_team !== null);

        if ($allCompleted) {
            $nextRoundNumber = $round->round_number + 1;
            if ($nextRoundNumber <= (int) $event->rounds) {
                // Generate next round automatically
                $nextRoundExists = OpenPlayRound::where('open_play_event_id', $event->id)
                    ->where('round_number', $nextRoundNumber)
                    ->whereHas('matches')
                    ->exists();

                if (! $nextRoundExists) {
                    $this->buildMatches($event, $nextRoundNumber);
                }
            } else {
                // If all rounds are completed, update event status to 'completed'
                $allRounds = OpenPlayRound::where('open_play_event_id', $event->id)->get();
                $eventCompleted = true;
                foreach ($allRounds as $r) {
                    if (OpenPlayMatch::where('open_play_round_id', $r->id)->whereNull('winner_team')->exists()) {
                        $eventCompleted = false;
                        break;
                    }
                }
                if ($eventCompleted) {
                    $event->update(['status' => 'completed']);
                }
            }
        }

        return back()->with('success', 'Match result submitted successfully.');
    }

    private function applyRatingChanges(OpenPlayMatch $match, int $winnerTeam, bool $revert = false): void
    {
        $team1PlayerIds = array_filter([$match->team1_player1_id, $match->team1_player2_id]);
        $team2PlayerIds = array_filter([$match->team2_player1_id, $match->team2_player2_id]);

        $winners = $winnerTeam === 1 ? $team1PlayerIds : $team2PlayerIds;
        $losers = $winnerTeam === 1 ? $team2PlayerIds : $team1PlayerIds;

        $ratingDiff = $revert ? -15 : 15;
        $winDiff = $revert ? -1 : 1;
        $lossDiff = $revert ? -1 : 1;
        $playedDiff = $revert ? -1 : 1;

        foreach ($winners as $uid) {
            $user = User::find($uid);
            if ($user) {
                $user->update([
                    'rating' => max(0, $user->rating + $ratingDiff),
                    'wins' => max(0, $user->wins + $winDiff),
                    'matches_played' => max(0, $user->matches_played + $playedDiff),
                ]);
            }
        }

        foreach ($losers as $uid) {
            $user = User::find($uid);
            if ($user) {
                $user->update([
                    'rating' => max(0, $user->rating - $ratingDiff),
                    'losses' => max(0, $user->losses + $lossDiff),
                    'matches_played' => max(0, $user->matches_played + $playedDiff),
                ]);
            }
        }
    }

    public function tv(Request $request): View
    {
        $settings = OpenPlaySetting::current();
        $event = OpenPlayEvent::upcoming($settings);

        $currentRound = null;
        $matches = collect();
        $waitingPlayers = collect();

        if ($event) {
            $currentRound = $event->rounds()->orderByDesc('round_number')->first();
            if ($currentRound) {
                $matches = $currentRound->matches()->with(['team1Player1', 'team1Player2', 'team2Player1', 'team2Player2'])->get();

                $presentUserIds = $event->activeRegistrations()
                    ->where('status', 'checked_in')
                    ->pluck('user_id')
                    ->all();

                $playingUserIds = [];
                foreach ($matches as $match) {
                    $playingUserIds = array_merge($playingUserIds, $match->playerIds());
                }

                $waitingUserIds = array_diff($presentUserIds, $playingUserIds);
                $waitingPlayersRaw = User::whereIn('id', $waitingUserIds)->get();

                // Sort waiting players based on priority logic
                $previousRounds = OpenPlayRound::query()
                    ->where('open_play_event_id', $event->id)
                    ->pluck('id')
                    ->all();

                $allEventMatches = OpenPlayMatch::query()
                    ->whereIn('open_play_round_id', $previousRounds)
                    ->get();

                $registrations = OpenPlayRegistration::query()
                    ->where('open_play_event_id', $event->id)
                    ->where('status', 'checked_in')
                    ->get()
                    ->keyBy('user_id');

                $waitingData = [];
                foreach ($waitingPlayersRaw as $user) {
                    $mCount = 0;
                    foreach ($allEventMatches as $m) {
                        if (in_array($user->id, $m->playerIds())) {
                            $mCount++;
                        }
                    }
                    $waitedLast = true;
                    if ($currentRound) {
                        $waitedLast = ! in_array($user->id, $playingUserIds);
                    }
                    $reg = $registrations->get($user->id);
                    $waitingData[] = [
                        'user' => $user,
                        'waited_last_round' => $waitedLast ? 1 : 0,
                        'matches_played' => $mCount,
                        'checked_in_at' => $reg ? $reg->checked_in_at : now(),
                    ];
                }

                usort($waitingData, function ($a, $b) {
                    if ($a['waited_last_round'] !== $b['waited_last_round']) {
                        return $b['waited_last_round'] <=> $a['waited_last_round'];
                    }
                    if ($a['matches_played'] !== $b['matches_played']) {
                        return $a['matches_played'] <=> $b['matches_played'];
                    }
                    return $a['checked_in_at'] <=> $b['checked_in_at'];
                });

                $waitingPlayers = collect(array_column($waitingData, 'user'));
            } else {
                $waitingPlayers = User::whereIn('id', $event->activeRegistrations()->where('status', 'checked_in')->pluck('user_id'))->get();
            }
        }

        $leaderboard = User::query()
            ->orderByDesc('rating')
            ->orderByDesc('wins')
            ->limit(10)
            ->get();

        return view('open-play.tv', [
            'event' => $event,
            'currentRound' => $currentRound,
            'matches' => $matches,
            'waitingPlayers' => $waitingPlayers,
            'leaderboard' => $leaderboard,
        ]);
    }

    public function leaderboard(Request $request): View
    {
        $players = User::query()
            ->orderByDesc('rating')
            ->orderByDesc('wins')
            ->paginate(50);

        return view('open-play.leaderboard', [
            'players' => $players,
        ]);
    }

    /* ===================== INTERNALS ===================== */

    private function buildMatches(OpenPlayEvent $event, int $roundNumber = 1): int
    {
        $presentUserIds = OpenPlayRegistration::query()
            ->where('open_play_event_id', $event->id)
            ->where('status', 'checked_in')
            ->pluck('user_id')
            ->all();

        if (count($presentUserIds) < 4) {
            return 0;
        }

        $courts = Court::query()
            ->where('is_active', true)
            ->when($event->location_id, fn ($q) => $q->where('location_id', $event->location_id))
            ->orderBy('court_number')
            ->get(['id', 'court_name', 'court_number']);

        if ($courts->isEmpty()) {
            return 0;
        }

        return DB::transaction(function () use ($event, $presentUserIds, $courts, $roundNumber) {
            // Find or create OpenPlayRound
            $round = OpenPlayRound::updateOrCreate(
                ['open_play_event_id' => $event->id, 'round_number' => $roundNumber]
            );

            // Delete any existing matches in this specific round if we are regenerating
            OpenPlayMatch::query()->where('open_play_round_id', $round->id)->delete();

            // Load matches history for the event (before this round number)
            $previousRounds = OpenPlayRound::query()
                ->where('open_play_event_id', $event->id)
                ->where('round_number', '<', $roundNumber)
                ->pluck('id')
                ->all();

            $previousMatches = OpenPlayMatch::query()
                ->whereIn('open_play_round_id', $previousRounds)
                ->get();

            // Last round player ids
            $lastRoundObj = OpenPlayRound::query()
                ->where('open_play_event_id', $event->id)
                ->where('round_number', $roundNumber - 1)
                ->first();
            $lastRoundMatches = $lastRoundObj
                ? OpenPlayMatch::query()->where('open_play_round_id', $lastRoundObj->id)->get()
                : collect();

            $lastRoundPlayerIds = [];
            foreach ($lastRoundMatches as $lm) {
                $lastRoundPlayerIds = array_merge($lastRoundPlayerIds, $lm->playerIds());
            }

            $registrations = OpenPlayRegistration::query()
                ->where('open_play_event_id', $event->id)
                ->where('status', 'checked_in')
                ->get()
                ->keyBy('user_id');

            $playerData = [];
            foreach ($presentUserIds as $uid) {
                $reg = $registrations->get($uid);
                $mCount = 0;
                foreach ($previousMatches as $pm) {
                    if (in_array($uid, $pm->playerIds())) {
                        $mCount++;
                    }
                }
                $waitedLast = true;
                if ($roundNumber > 1) {
                    $waitedLast = ! in_array($uid, $lastRoundPlayerIds);
                }
                $playerData[] = [
                    'user_id' => $uid,
                    'waited_last_round' => $waitedLast ? 1 : 0,
                    'matches_played' => $mCount,
                    'checked_in_at' => $reg ? $reg->checked_in_at : now(),
                ];
            }

            // Sort players: waited_last_round desc, matches_played asc, checked_in_at asc
            usort($playerData, function ($a, $b) {
                if ($a['waited_last_round'] !== $b['waited_last_round']) {
                    return $b['waited_last_round'] <=> $a['waited_last_round'];
                }
                if ($a['matches_played'] !== $b['matches_played']) {
                    return $a['matches_played'] <=> $b['matches_played'];
                }
                return $a['checked_in_at'] <=> $b['checked_in_at'];
            });

            $sortedPlayerIds = array_column($playerData, 'user_id');
            $maxCourts = min($courts->count(), (int) floor(count($sortedPlayerIds) / 4));

            if ($maxCourts === 0) {
                return 0;
            }

            $selectedPlayerIds = array_slice($sortedPlayerIds, 0, $maxCourts * 4);

            // Group by rating
            $usersWithRatings = User::whereIn('id', $selectedPlayerIds)->get(['id', 'rating'])->keyBy('id');

            usort($selectedPlayerIds, function ($a, $b) use ($usersWithRatings) {
                $rA = $usersWithRatings->get($a)->rating ?? 1000;
                $rB = $usersWithRatings->get($b)->rating ?? 1000;
                return $rB <=> $rA; // descending
            });

            $courtGroups = array_chunk($selectedPlayerIds, 4);

            // Build teammate and opponent history from previous matches in this event
            $teammateCounts = [];
            $opponentCounts = [];
            foreach ($previousMatches as $pm) {
                $pIds1 = [$pm->team1_player1_id, $pm->team1_player2_id];
                $pIds2 = [$pm->team2_player1_id, $pm->team2_player2_id];

                $t1Key = min($pIds1[0], $pIds1[1]).'-'.max($pIds1[0], $pIds1[1]);
                $teammateCounts[$t1Key] = ($teammateCounts[$t1Key] ?? 0) + 1;

                $t2Key = min($pIds2[0], $pIds2[1]).'-'.max($pIds2[0], $pIds2[1]);
                $teammateCounts[$t2Key] = ($teammateCounts[$t2Key] ?? 0) + 1;

                foreach ($pIds1 as $id1) {
                    foreach ($pIds2 as $id2) {
                        $oKey = min($id1, $id2).'-'.max($id1, $id2);
                        $opponentCounts[$oKey] = ($opponentCounts[$oKey] ?? 0) + 1;
                    }
                }
            }

            $created = 0;
            foreach ($courtGroups as $index => $courtGroup) {
                if (count($courtGroup) < 4) {
                    break;
                }

                $bestArrangement = null;
                $bestScore = PHP_INT_MAX;

                $arrangements = [
                    [$courtGroup[0], $courtGroup[3], $courtGroup[1], $courtGroup[2]], // P1+P4 vs P2+P3
                    [$courtGroup[0], $courtGroup[2], $courtGroup[1], $courtGroup[3]], // P1+P3 vs P2+P4
                    [$courtGroup[0], $courtGroup[1], $courtGroup[2], $courtGroup[3]], // P1+P2 vs P3+P4
                ];

                foreach ($arrangements as $arr) {
                    [$a, $b, $c, $d] = $arr;

                    $t1Key = min($a, $b).'-'.max($a, $b);
                    $t2Key = min($c, $d).'-'.max($c, $d);
                    $tCount = ($teammateCounts[$t1Key] ?? 0) + ($teammateCounts[$t2Key] ?? 0);

                    $oCount = ($opponentCounts[min($a, $c).'-'.max($a, $c)] ?? 0)
                            + ($opponentCounts[min($a, $d).'-'.max($a, $d)] ?? 0)
                            + ($opponentCounts[min($b, $c).'-'.max($b, $c)] ?? 0)
                            + ($opponentCounts[min($b, $d).'-'.max($b, $d)] ?? 0);

                    $rA = $usersWithRatings->get($a)->rating ?? 1000;
                    $rB = $usersWithRatings->get($b)->rating ?? 1000;
                    $rC = $usersWithRatings->get($c)->rating ?? 1000;
                    $rD = $usersWithRatings->get($d)->rating ?? 1000;
                    $ratingDiff = abs(($rA + $rB) - ($rC + $rD));

                    $score = ($tCount * 1000) + ($oCount * 100) + $ratingDiff;

                    if ($score < $bestScore) {
                        $bestScore = $score;
                        $bestArrangement = $arr;
                    }
                }

                [$a, $b, $c, $d] = $bestArrangement;

                $court = $courts->count() ? $courts[$index % $courts->count()] : null;

                OpenPlayMatch::query()->create([
                    'open_play_round_id' => $round->id,
                    'open_play_event_id' => $event->id,
                    'court_id' => $court->id ?? null,
                    'court_label' => $court ? ($court->court_name ?: 'Court '.$court->court_number) : 'Court '.($index + 1),
                    'team1_player1_id' => $a,
                    'team1_player2_id' => $b,
                    'team2_player1_id' => $c,
                    'team2_player2_id' => $d,
                    'winner_team' => null,
                ]);

                $created++;
            }

            return $created;
        });
    }

    /** @return array<int, array<string, mixed>> */
    private function roundsView(OpenPlayEvent $event): array
    {
        $rounds = OpenPlayRound::query()
            ->where('open_play_event_id', $event->id)
            ->with('matches')
            ->orderBy('round_number')
            ->get();

        $ids = [];
        foreach ($rounds as $round) {
            foreach ($round->matches as $m) {
                $ids = array_merge($ids, $m->playerIds());
            }
        }
        $names = $this->playerNames(array_unique($ids));

        return $rounds->map(fn ($round) => [
            'round_number' => $round->round_number,
            'matches' => $round->matches->map(fn ($m) => [
                'id' => $m->id,
                'court' => $m->court_label,
                'winner_team' => $m->winner_team,
                'team1_player1_id' => $m->team1_player1_id,
                'team1_player2_id' => $m->team1_player2_id,
                'team2_player1_id' => $m->team2_player1_id,
                'team2_player2_id' => $m->team2_player2_id,
                'team1' => [$names[$m->team1_player1_id] ?? '—', $names[$m->team1_player2_id] ?? '—'],
                'team2' => [$names[$m->team2_player1_id] ?? '—', $names[$m->team2_player2_id] ?? '—'],
            ])->all(),
        ])->all();
    }

    /** @return array<int, array<string,mixed>> */
    private function matchesForUser(OpenPlayEvent $event, int $userId): array
    {
        $rounds = OpenPlayRound::query()
            ->where('open_play_event_id', $event->id)
            ->with('matches')
            ->orderBy('round_number')
            ->get();

        $ids = [];
        foreach ($rounds as $round) {
            foreach ($round->matches as $m) {
                $ids = array_merge($ids, $m->playerIds());
            }
        }
        $names = $this->playerNames(array_unique($ids));

        $result = [];
        foreach ($rounds as $round) {
            foreach ($round->matches as $m) {
                if (! in_array($userId, $m->playerIds(), true)) {
                    continue;
                }

                $isTeam1 = in_array($userId, [$m->team1_player1_id, $m->team1_player2_id], true);
                $partnerId = $isTeam1
                    ? ($m->team1_player1_id === $userId ? $m->team1_player2_id : $m->team1_player1_id)
                    : ($m->team2_player1_id === $userId ? $m->team2_player2_id : $m->team2_player1_id);
                $opponents = $isTeam1
                    ? [$m->team2_player1_id, $m->team2_player2_id]
                    : [$m->team1_player1_id, $m->team1_player2_id];

                $result[] = [
                    'round_number' => $round->round_number,
                    'court' => $m->court_label,
                    'winner_team' => $m->winner_team,
                    'is_winner' => $m->winner_team !== null && (
                        ((int) $m->winner_team === 1 && $isTeam1) ||
                        ((int) $m->winner_team === 2 && ! $isTeam1)
                    ),
                    'partner' => $names[$partnerId] ?? 'TBD',
                    'opponents' => array_map(fn ($id) => $names[$id] ?? 'TBD', $opponents),
                ];
            }
        }

        return $result;
    }

    /** @param array<int> $userIds @return array<int,string> */
    private function playerNames(array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }

        $profiles = EndUserProfile::query()
            ->whereIn('user_id', $userIds)
            ->get(['user_id', 'first_name', 'last_name'])
            ->keyBy('user_id');

        $emails = $this->playerEmails($userIds);

        $names = [];
        foreach ($userIds as $id) {
            $profile = $profiles->get($id);
            $name = $profile ? trim(($profile->first_name ?? '').' '.($profile->last_name ?? '')) : '';
            $names[$id] = $name !== '' ? $name : ($emails[$id] ?? ('User #'.$id));
        }

        return $names;
    }

    /** @param array<int> $userIds @return array<int,string> */
    private function playerEmails(array $userIds): array
    {
        if (empty($userIds)) {
            return [];
        }

        return User::query()->whereIn('id', $userIds)->pluck('email', 'id')->all();
    }

    private function isStaff(User $user): bool
    {
        return $user->hasAnyRole([
            User::ROLE_SUPER_ADMIN,
            User::ROLE_ADMIN,
            User::ROLE_LOCATION_MANAGER,
            User::ROLE_STAFF,
        ]);
    }

    private function ensureStaff(User $user): void
    {
        abort_unless($this->isStaff($user), 403);
    }
}
