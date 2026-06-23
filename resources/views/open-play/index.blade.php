<x-app-layout>
    <x-slot name="header">Open Play</x-slot>

    @php
        $days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
    @endphp

    <div class="container-fluid py-4">



        {{-- ===== Upcoming Open Play ===== --}}
        <div class="row">
            <div class="col-lg-7 mb-4">
                @if ($event)
                    <div class="card overflow-hidden">
                        <div class="card-header bg-gradient-dark">
                            <div class="d-flex justify-content-between align-items-center flex-wrap">
                                <div>
                                    <h5 class="text-white mb-1"><i class="fas fa-table-tennis text-warning me-2"></i>Open Play</h5>
                                    <p class="text-white text-xs opacity-8 mb-0">Join the next pickleball Open Play session.</p>
                                </div>
                                <span class="badge bg-gradient-{{ $event->isFull() ? 'danger' : 'success' }}" id="opStatusBadge">
                                    {{ $event->isFull() ? 'OPEN PLAY FULL' : 'REGISTRATION OPEN' }}
                                </span>
                            </div>
                        </div>
                        <div class="card-body" data-op-slots-url="{{ route('open-play.slots', $event->id) }}">
                            <div class="row g-3 mb-3">
                                <div class="col-6 col-md-3">
                                    <p class="text-xs text-secondary mb-0">Day</p>
                                    <h6 class="mb-0">{{ $days[$event->day_of_week] ?? '—' }}</h6>
                                    <span class="text-xs text-secondary">{{ $event->event_date->format('M d, Y') }}</span>
                                </div>
                                <div class="col-6 col-md-3">
                                    <p class="text-xs text-secondary mb-0">Time</p>
                                    <h6 class="mb-0">{{ \Carbon\Carbon::parse($event->start_time)->format('g:i A') }}</h6>
                                    <span class="text-xs text-secondary">to {{ \Carbon\Carbon::parse($event->end_time)->format('g:i A') }}</span>
                                </div>
                                <div class="col-6 col-md-3">
                                    <p class="text-xs text-secondary mb-0">Entrance Fee</p>
                                    <h6 class="mb-0 text-success">PHP {{ number_format($event->entrance_fee, 2) }}</h6>
                                </div>
                                <div class="col-6 col-md-3">
                                    <p class="text-xs text-secondary mb-0">Remaining Slots</p>
                                    <h6 class="mb-0"><span id="opRemaining">{{ $event->remainingSlots() }}</span> / {{ $event->max_slots }}</h6>
                                </div>
                            </div>

                            <div class="progress mb-3" style="height: 8px;">
                                <div class="progress-bar bg-gradient-success" id="opProgress" role="progressbar"
                                     style="width: {{ $event->max_slots ? min(100, round($event->takenSlots() / $event->max_slots * 100)) : 0 }}%"></div>
                            </div>

                            @if ($myRegistration)
                                @if ($myRegistration->status === 'checked_in')
                                    <div class="alert alert-success text-white d-flex align-items-center justify-content-between flex-wrap gap-2" role="alert">
                                        <span><i class="fas fa-check-circle me-2"></i>You're Checked In — Present (Slot #{{ $myRegistration->slot_number }})</span>
                                        <a href="{{ route('open-play.ticket', $myRegistration->id) }}" class="btn btn-sm bg-white text-dark mb-0">View Ticket</a>
                                    </div>
                                @elseif ($myRegistration->status === 'pending_payment')
                                    @php
                                        $secondsLeft = max(0, 180 - now()->diffInSeconds($myRegistration->registered_at));
                                    @endphp
                                    <div class="alert alert-danger text-white p-3 border-radius-lg mb-0" role="alert">
                                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                                            <span><i class="fas fa-money-bill-wave me-2"></i>Pending Payment for Slot #{{ $myRegistration->slot_number }}</span>
                                            <a href="{{ route('open-play.pay', $myRegistration->id) }}" class="btn btn-sm bg-white text-dark mb-0"><i class="fas fa-wallet me-1"></i>Pay Now via GCash</a>
                                        </div>
                                        <p class="text-xs mb-0 text-white">
                                            Your slot is reserved temporarily. Pay via GCash using the button above to confirm, or pay cash to staff at the venue.
                                            <strong>Hold expires in: <span id="paymentCountdown" data-seconds="{{ $secondsLeft }}">--:--</span></strong>
                                        </p>
                                    </div>
                                @else
                                    <div class="alert alert-warning text-white p-3 border-radius-lg mb-0" role="alert">
                                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                                            <span><i class="fas fa-exclamation-triangle me-2"></i>Registered for Slot #{{ $myRegistration->slot_number }} (Absent)</span>
                                            <a href="{{ route('open-play.ticket', $myRegistration->id) }}" class="btn btn-sm bg-white text-dark mb-0">View Ticket</a>
                                        </div>
                                        
                                        @if ($event->isAttendanceLocked())
                                            <p class="text-xs mb-0 text-white"><i class="fas fa-lock me-1"></i>Attendance is locked. You can no longer self check-in.</p>
                                        @else
                                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                                <p class="text-xs mb-0 text-white">
                                                    <i class="fas fa-clock me-1"></i>Check-in closes in: 
                                                    <strong id="attendanceCountdown" data-seconds="{{ $event->attendanceRemainingSeconds() }}">--:--</strong>
                                                </p>
                                                <form method="POST" action="{{ route('open-play.self-checkin', $event->id) }}" class="mb-0">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm bg-gradient-success mb-0 py-1 px-3">
                                                        <i class="fas fa-check me-1"></i>Check In
                                                    </button>
                                                </form>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            @else
                                <form method="POST" action="{{ route('open-play.join', $event->id) }}" data-confirm="Join Open Play on {{ $event->event_date->format('M d') }} for PHP {{ number_format($event->entrance_fee, 2) }}?">
                                    @csrf
                                    <button type="submit" id="opJoinBtn" class="btn bg-gradient-success w-100 mb-0" {{ $event->isFull() ? 'disabled' : '' }}>
                                        <i class="fas fa-user-plus me-1"></i>
                                        <span id="opJoinLabel">{{ $event->isFull() ? 'OPEN PLAY FULL' : 'Join Open Play' }}</span>
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                @else
                    <div class="card">
                        <div class="card-body text-center text-secondary py-5">
                            <i class="fas fa-table-tennis fa-2x mb-3 d-block opacity-5"></i>
                            <h6>No Open Play scheduled</h6>
                            <p class="text-sm mb-0">Open Play is currently turned off. Check back later.</p>
                        </div>
                    </div>
                @endif
            </div>

            {{-- ===== My Rating & Assigned Matches ===== --}}
            <div class="col-lg-5 mb-4">
                {{-- Player Profile & Rating Card --}}
                <div class="card mb-4">
                    <div class="card-header pb-0">
                        <h6><i class="fas fa-trophy me-2 text-warning"></i>Player Profile & Rating</h6>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar avatar-xl position-relative me-3">
                                <span class="fs-1">{{ Auth::user()->badge_emoji }}</span>
                            </div>
                            <div>
                                <h6 class="mb-0">{{ Auth::user()->endUserProfile ? Auth::user()->endUserProfile->first_name . ' ' . Auth::user()->endUserProfile->last_name : Auth::user()->email }}</h6>
                                <span class="badge bg-gradient-info text-xs">{{ Auth::user()->badge_name }} Division</span>
                            </div>
                        </div>
                        <div class="row text-center bg-gray-100 border-radius-lg p-3">
                            <div class="col-4">
                                <span class="text-xs text-secondary d-block">Rating</span>
                                <strong class="text-lg text-dark">{{ Auth::user()->rating }}</strong>
                            </div>
                            <div class="col-4 border-start border-end">
                                <span class="text-xs text-secondary d-block">Win Rate</span>
                                <strong class="text-lg text-dark">{{ Auth::user()->win_rate }}%</strong>
                            </div>
                            <div class="col-4">
                                <span class="text-xs text-secondary d-block">Record</span>
                                <strong class="text-xs text-dark">{{ Auth::user()->wins }}W - {{ Auth::user()->losses }}L</strong>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Matches Card --}}
                <div class="card h-auto">
                    <div class="card-header pb-0"><h6><i class="fas fa-sitemap me-2 text-warning"></i>My Matches</h6></div>
                    <div class="card-body">
                        @if (! empty($myMatches))
                            @foreach ($myMatches as $m)
                                <div class="border border-radius-lg p-3 mb-2">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="badge bg-gradient-info">Round {{ $m['round_number'] }}</span>
                                        <span class="text-xs text-secondary"><i class="fas fa-map-pin me-1"></i>{{ $m['court'] }}</span>
                                    </div>
                                    <p class="text-xs mb-1"><span class="text-secondary">Partner:</span> <strong>{{ $m['partner'] }}</strong></p>
                                    <p class="text-xs mb-1"><span class="text-secondary">Opponents:</span> <strong>{{ implode(' & ', $m['opponents']) }}</strong></p>
                                    @if ($m['winner_team'] !== null)
                                        <p class="text-xs mb-0">
                                            <span class="text-secondary">Result:</span> 
                                            @if ($m['is_winner'])
                                                <span class="badge bg-gradient-success text-xxs">WIN (+15 rating)</span>
                                            @else
                                                <span class="badge bg-gradient-danger text-xxs">LOSS (-15 rating)</span>
                                            @endif
                                        </p>
                                    @endif
                                </div>
                            @endforeach
                        @elseif ($myRegistration)
                            <p class="text-center text-secondary py-4 mb-0">Matches not generated yet. Check back once registration closes.</p>
                        @else
                            <p class="text-center text-secondary py-4 mb-0">Join Open Play to get match assignments.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== My Registered Events ===== --}}
        <div class="row">
            <div class="col-12 mb-4">
                <div class="card">
                    <div class="card-header pb-0"><h6><i class="fas fa-history me-2 text-info"></i>My Open Play Registrations</h6></div>
                    <div class="card-body px-0 pt-0">
                        <div class="table-responsive">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder ps-3">Date</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder">Time</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder">Slot</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder">Fee</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder">Status</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder text-center">Ticket</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($myRegistrations as $reg)
                                        <tr>
                                            <td class="ps-3 text-sm">{{ optional($reg->event)->event_date?->format('M d, Y') ?? '—' }}</td>
                                            <td class="text-sm">{{ optional($reg->event) ? \Carbon\Carbon::parse($reg->event->start_time)->format('g:i A') : '—' }}</td>
                                            <td><span class="badge bg-gradient-dark">#{{ $reg->slot_number }}</span></td>
                                            <td class="text-sm">PHP {{ number_format((float) $reg->amount_paid, 2) }}</td>
                                            <td><span class="badge bg-gradient-{{ $reg->status === 'checked_in' ? 'success' : 'secondary' }}">{{ ucfirst(str_replace('_',' ', $reg->status)) }}</span></td>
                                            <td class="text-center">
                                                <a href="{{ route('open-play.ticket', $reg->id) }}" class="btn btn-sm bg-gradient-info mb-0 py-1 px-2" style="font-size:11px;"><i class="fas fa-ticket-alt me-1"></i>View</a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="6" class="text-center text-secondary py-4">You have not registered for any Open Play yet.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Real-time slot counter for the public join card.
        (function () {
            const wrap = document.querySelector('[data-op-slots-url]');
            if (!wrap) return;
            const url = wrap.dataset.opSlotsUrl;
            async function tick() {
                try {
                    const r = await fetch(url, { headers: { 'Accept': 'application/json' } });
                    if (!r.ok) return;
                    const d = await r.json();
                    const remaining = document.getElementById('opRemaining');
                    const progress = document.getElementById('opProgress');
                    const badge = document.getElementById('opStatusBadge');
                    const joinBtn = document.getElementById('opJoinBtn');
                    const joinLabel = document.getElementById('opJoinLabel');
                    if (remaining) remaining.textContent = d.remaining;
                    if (progress && d.max_slots) progress.style.width = Math.min(100, Math.round(d.taken / d.max_slots * 100)) + '%';
                    if (badge) {
                        badge.textContent = d.is_full ? 'OPEN PLAY FULL' : 'REGISTRATION OPEN';
                        badge.classList.toggle('bg-gradient-danger', d.is_full);
                        badge.classList.toggle('bg-gradient-success', !d.is_full);
                    }
                    if (joinBtn) {
                        joinBtn.disabled = d.is_full;
                        if (joinLabel) joinLabel.textContent = d.is_full ? 'OPEN PLAY FULL' : 'Join Open Play';
                    }
                } catch (e) {}
            }
            setInterval(tick, 8000);
        })();

        // Attendance countdown timer.
        (function () {
            const timerEl = document.getElementById('attendanceCountdown');
            if (!timerEl) return;

            let secondsLeft = parseInt(timerEl.dataset.seconds, 10);
            
            function updateTimer() {
                if (secondsLeft <= 0) {
                    timerEl.textContent = "Locked";
                    clearInterval(interval);
                    setTimeout(() => window.location.reload(), 1500);
                    return;
                }

                const mins = Math.floor(secondsLeft / 60);
                const secs = secondsLeft % 60;
                timerEl.textContent = `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
                secondsLeft--;
            }

            updateTimer();
            const interval = setInterval(updateTimer, 1000);
        })();

        // Payment hold countdown timer.
        (function () {
            const timerEl = document.getElementById('paymentCountdown');
            if (!timerEl) return;

            let secondsLeft = parseInt(timerEl.dataset.seconds, 10);
            
            function updateTimer() {
                if (secondsLeft <= 0) {
                    timerEl.textContent = "Expired";
                    clearInterval(interval);
                    setTimeout(() => window.location.reload(), 1000);
                    return;
                }

                const mins = Math.floor(secondsLeft / 60);
                const secs = secondsLeft % 60;
                timerEl.textContent = `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
                secondsLeft--;
            }

            updateTimer();
            const interval = setInterval(updateTimer, 1000);
        })();

        @if ($myRegistration && $myRegistration->status === 'pending_payment')
        // Poll payment status to auto-reload when paid.
        (function () {
            const url = "{{ route('open-play.registration-status', $myRegistration->id) }}";
            async function checkStatus() {
                try {
                    const r = await fetch(url, { headers: { 'Accept': 'application/json' } });
                    if (!r.ok) return;
                    const d = await r.json();
                    if (d.status === 'registered' || d.status === 'checked_in') {
                        window.location.reload();
                    }
                } catch (e) {}
            }
            setInterval(checkStatus, 4000);
        })();
        @endif
    </script>
</x-app-layout>
