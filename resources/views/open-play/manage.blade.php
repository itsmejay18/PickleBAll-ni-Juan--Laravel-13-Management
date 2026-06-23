<x-app-layout>
    <x-slot name="header">Open Play Manager</x-slot>

    @php
        $days = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
        $t = fn ($v) => \Illuminate\Support\Str::of((string) $v)->substr(0, 5);
        $checkedIn = $participants->where('status', 'checked_in')->count();
    @endphp

    <div class="container-fluid py-4">



        {{-- ===== Status / Stats Card ===== --}}
        <div class="row">
            <div class="col-12 mb-4">
                <div class="card">
                    <div class="card-header pb-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h6 class="mb-0"><i class="fas fa-table-tennis text-success me-2"></i>Open Play
                                @if ($settings->is_enabled)
                                    <span class="badge bg-gradient-success ms-2" id="opStatusBadge">ON</span>
                                @else
                                    <span class="badge bg-gradient-secondary ms-2" id="opStatusBadge">OFF</span>
                                @endif
                            </h6>
                            <p class="text-sm mb-0 text-secondary">Manage the weekly Open Play event, players, and matches.</p>
                        </div>
                        @if ($event)
                            <a href="{{ route('open-play.export', $event->id) }}" class="btn btn-sm bg-gradient-dark mb-0"><i class="fas fa-file-export me-1"></i>Export Participants</a>
                        @endif
                    </div>
                    <div class="card-body">
                        @if ($event)
                            <div class="row g-3" data-op-event="{{ $event->id }}" data-op-slots-url="{{ route('open-play.slots', $event->id) }}">
                                <div class="col-6 col-md-3 col-lg-2">
                                    <div class="border-radius-lg bg-gradient-info p-3 text-white text-center h-100">
                                        <p class="text-xs text-uppercase mb-1 opacity-8">Day</p>
                                        <h6 class="text-white mb-0">{{ $days[$event->day_of_week] ?? '—' }}</h6>
                                        <span class="text-xs">{{ $event->event_date->format('M d, Y') }}</span>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3 col-lg-2">
                                    <div class="border-radius-lg bg-gradient-primary p-3 text-white text-center h-100">
                                        <p class="text-xs text-uppercase mb-1 opacity-8">Time</p>
                                        <h6 class="text-white mb-0">{{ \Carbon\Carbon::parse($event->start_time)->format('g:i A') }}</h6>
                                        <span class="text-xs">to {{ \Carbon\Carbon::parse($event->end_time)->format('g:i A') }}</span>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3 col-lg-2">
                                    <div class="border-radius-lg bg-gradient-success p-3 text-white text-center h-100">
                                        <p class="text-xs text-uppercase mb-1 opacity-8">Entrance Fee</p>
                                        <h6 class="text-white mb-0">PHP {{ number_format($event->entrance_fee, 2) }}</h6>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3 col-lg-2">
                                    <div class="border-radius-lg bg-gradient-dark p-3 text-white text-center h-100">
                                        <p class="text-xs text-uppercase mb-1 opacity-8">Max Slots</p>
                                        <h6 class="text-white mb-0">{{ $event->max_slots }}</h6>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3 col-lg-2">
                                    <div class="border-radius-lg bg-gradient-warning p-3 text-white text-center h-100">
                                        <p class="text-xs text-uppercase mb-1 opacity-8">Registered</p>
                                        <h6 class="text-white mb-0"><span id="opTaken">{{ $event->takenSlots() }}</span></h6>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3 col-lg-2">
                                    <div class="border-radius-lg bg-gradient-{{ $event->isFull() ? 'danger' : 'success' }} p-3 text-white text-center h-100" id="opRemainingCard">
                                        <p class="text-xs text-uppercase mb-1 opacity-8">Remaining</p>
                                        <h6 class="text-white mb-0"><span id="opRemaining">{{ $event->remainingSlots() }}</span></h6>
                                        <span class="text-xs" id="opFullLabel">{{ $event->isFull() ? 'OPEN PLAY FULL' : 'slots left' }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-3 g-3">
                                <div class="col-md-3 col-6">
                                    <p class="text-xs text-secondary mb-0">Total Participants</p>
                                    <h5 class="mb-0">{{ $participants->count() }}</h5>
                                </div>
                                <div class="col-md-3 col-6">
                                    <p class="text-xs text-secondary mb-0">Checked In</p>
                                    <h5 class="mb-0 text-success">{{ $checkedIn }}</h5>
                                </div>
                                <div class="col-md-3 col-6">
                                    <p class="text-xs text-secondary mb-0">Available Slots</p>
                                    <h5 class="mb-0">{{ $event->max_slots }}</h5>
                                </div>
                                <div class="col-md-3 col-6">
                                    <p class="text-xs text-secondary mb-0">Matches</p>
                                    <h5 class="mb-0">{{ $event->matches_generated ? 'Generated' : 'Not yet' }}</h5>
                                </div>
                            </div>
                        @else
                            <div class="text-center text-secondary py-4">
                                <i class="fas fa-power-off fa-2x mb-2 d-block opacity-5"></i>
                                Open Play is currently <strong>OFF</strong>. Turn it on below to create the next event.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== Settings ===== --}}
        <div class="row">
            <div class="col-lg-5 mb-4">
                <div class="card h-100">
                    <div class="card-header pb-0"><h6><i class="fas fa-sliders-h me-2 text-info"></i>Settings & Schedule</h6></div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('open-play.settings.update') }}">
                            @csrf
                            <div class="form-check form-switch ps-0 mb-3 d-flex align-items-center">
                                <input class="form-check-input ms-0 me-2" type="checkbox" name="is_enabled" id="is_enabled" value="1" {{ $settings->is_enabled ? 'checked' : '' }}>
                                <label class="form-check-label text-sm font-weight-bold mb-0" for="is_enabled">Enable Open Play (show on public dashboard)</label>
                            </div>

                            <label class="form-label text-xs">Open Play Day</label>
                            <select name="day_of_week" class="form-control mb-3">
                                @foreach ($days as $i => $name)
                                    <option value="{{ $i }}" {{ (int) $settings->day_of_week === $i ? 'selected' : '' }}>{{ $name }}</option>
                                @endforeach
                            </select>

                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label class="form-label text-xs">Open Play Start</label>
                                    <input type="time" name="open_play_start" class="form-control" value="{{ old('open_play_start', $t($settings->open_play_start)) }}" required>
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label text-xs">Open Play End</label>
                                    <input type="time" name="open_play_end" class="form-control" value="{{ old('open_play_end', $t($settings->open_play_end)) }}" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label text-xs">Attendance Closing Time</label>
                                <input type="time" name="attendance_closing_time" class="form-control" value="{{ old('attendance_closing_time', $t($settings->attendance_closing_time)) }}" required>
                            </div>

                            <p class="text-xs text-uppercase text-secondary mb-2">Normal Booking — Before Open Play</p>
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label class="form-label text-xs">From</label>
                                    <input type="time" name="booking_open_start" class="form-control" value="{{ old('booking_open_start', $t($settings->booking_open_start)) }}" required>
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label text-xs">Until</label>
                                    <input type="time" name="booking_open_end" class="form-control" value="{{ old('booking_open_end', $t($settings->booking_open_end)) }}" required>
                                </div>
                            </div>

                            <p class="text-xs text-uppercase text-secondary mb-2">Normal Booking — After Open Play</p>
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label class="form-label text-xs">From</label>
                                    <input type="time" name="booking_resume_start" class="form-control" value="{{ old('booking_resume_start', $t($settings->booking_resume_start)) }}" required>
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label text-xs">Until</label>
                                    <input type="time" name="booking_resume_end" class="form-control" value="{{ old('booking_resume_end', $t($settings->booking_resume_end)) }}" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label class="form-label text-xs">Entrance Fee (PHP)</label>
                                    <input type="number" step="0.01" min="0" name="entrance_fee" class="form-control" value="{{ old('entrance_fee', $settings->entrance_fee) }}" required>
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label text-xs">Maximum Slots</label>
                                    <input type="number" min="4" max="500" name="max_slots" class="form-control" value="{{ old('max_slots', $settings->max_slots) }}" required>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label class="form-label text-xs">Match Rounds</label>
                                    <input type="number" min="1" max="12" name="rounds" class="form-control" value="{{ old('rounds', $settings->rounds) }}" required>
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label text-xs">Location (optional)</label>
                                    <select name="location_id" class="form-control">
                                        <option value="">All / Default</option>
                                        @foreach ($locations as $loc)
                                            <option value="{{ $loc->id }}" {{ (int) $settings->location_id === (int) $loc->id ? 'selected' : '' }}>{{ $loc->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <button type="submit" class="btn bg-gradient-success w-100 mb-0"><i class="fas fa-save me-1"></i>Save Settings</button>
                        </form>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header pb-0">
                        <h6><i class="fas fa-money-bill-wave me-2 text-success"></i>Cash Registration</h6>
                    </div>
                    <div class="card-body">
                        @if ($event)
                            <form method="GET" action="{{ route('open-play.manage') }}" class="mb-3">
                                @if (request('q'))
                                    <input type="hidden" name="q" value="{{ request('q') }}">
                                @endif
                                <div class="mb-3">
                                    <label class="form-label text-xs">Search Player Accounts (Signed Up)</label>
                                    <div class="input-group">
                                        <input type="text" name="user_q" value="{{ $userSearch }}" class="form-control" placeholder="Search name or email..." required>
                                        <button class="btn bg-gradient-info mb-0" type="submit">
                                            <i class="fas fa-search"></i>
                                        </button>
                                    </div>
                                </div>
                            </form>

                            <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                                <table class="table align-items-center mb-0 table-sm">
                                    <thead>
                                        <tr>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder ps-2">Name / Email</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder text-end pe-2">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($unregisteredUsers as $u)
                                            <tr>
                                                <td class="ps-2">
                                                    <span class="text-xs font-weight-bold">{{ $u->display_label }}</span>
                                                </td>
                                                <td class="text-end pe-2">
                                                    <form method="POST" action="{{ route('open-play.register-cash', $event->id) }}" class="mb-0 d-inline">
                                                        @csrf
                                                        <input type="hidden" name="user_id" value="{{ $u->id }}">
                                                        <button type="submit" class="btn btn-xs bg-gradient-success mb-0 py-1" onclick="return confirm('Register {{ $u->display_label }} with Cash payment?')">
                                                            <i class="fas fa-plus"></i> Give Slot
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="2" class="text-center text-xs text-secondary py-3">
                                                    No unregistered players found.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            @if ($userSearch !== '')
                                <div class="text-center mt-3">
                                    <a href="{{ route('open-play.manage') }}{{ request('q') ? '?q=' . urlencode(request('q')) : '' }}" class="text-xs text-info">
                                        <i class="fas fa-sync-alt me-1"></i>Reset User Search
                                    </a>
                                </div>
                            @endif
                        @else
                            <p class="text-center text-xs text-secondary py-3">Enable Open Play to add cash slots.</p>
                        @endif
                    </div>
                </div>
            </div>

            {{-- ===== Participants ===== --}}
            <div class="col-lg-7 mb-4">
                <div class="card h-100">
                    <div class="card-header pb-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h6 class="mb-0"><i class="fas fa-users me-2 text-primary"></i>Registered Players ({{ $participants->count() }})</h6>
                        <form method="GET" action="{{ route('open-play.manage') }}" class="d-flex gap-2 mb-0">
                            <input type="text" name="q" value="{{ $search }}" placeholder="Search name / email / slot" class="form-control form-control-sm" style="width: 200px;">
                            <button class="btn btn-sm bg-gradient-info mb-0"><i class="fas fa-search"></i></button>
                        </form>
                    </div>
                    <div class="card-body px-0 pt-0">
                        @if ($event)
                            <div class="d-flex gap-2 px-3 mb-3 flex-wrap">
                                <form method="POST" action="{{ $event->matches_generated ? route('open-play.matches.regenerate', $event->id) : route('open-play.matches.generate', $event->id) }}" data-confirm="{{ $event->matches_generated ? 'Reshuffle and replace matches for the active round?' : 'Start Open Play and generate matches for Round 1?' }}" class="mb-0">
                                    @csrf
                                    <button type="submit" class="btn btn-sm bg-gradient-warning mb-0">
                                        <i class="fas fa-random me-1"></i>{{ $event->matches_generated ? 'Regenerate Active Round' : 'Start Open Play & Generate Round 1' }}
                                    </button>
                                </form>
                                @if ($event->matches_generated)
                                    <span class="badge bg-gradient-success align-self-center">Matches ready — scroll down</span>
                                @endif
                            </div>

                            <div class="table-responsive">
                                <table class="table align-items-center mb-0">
                                    <thead>
                                        <tr>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder ps-3">Slot</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder">Player</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder">Status</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder text-center">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($participants as $p)
                                            <tr>
                                                <td class="ps-3"><span class="badge bg-gradient-dark">#{{ $p->slot_number }}</span></td>
                                                <td>
                                                    <h6 class="mb-0 text-sm">{{ $p->display_name }}</h6>
                                                    <span class="text-xs text-secondary">{{ $p->email }}</span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-gradient-{{ $p->status === 'checked_in' ? 'success' : ($p->status === 'pending_payment' ? 'danger' : 'secondary') }}">{{ ucfirst(str_replace('_',' ', $p->status)) }}</span>
                                                </td>
                                                <td class="text-center">
                                                    <div class="d-flex gap-1 justify-content-center">
                                                        @if ($p->status === 'pending_payment')
                                                            <form method="POST" action="{{ route('open-play.mark-cash-paid', $p->id) }}" class="mb-0">
                                                                @csrf
                                                                <button type="submit" class="btn btn-sm bg-gradient-info mb-0 py-1 px-2" style="font-size:11px;">
                                                                    <i class="fas fa-money-bill-wave me-1"></i>Mark Paid
                                                                </button>
                                                            </form>
                                                        @else
                                                            <form method="POST" action="{{ route('open-play.checkin', $p->id) }}" class="mb-0">
                                                                @csrf
                                                                <button type="submit" class="btn btn-sm {{ $p->status === 'checked_in' ? 'bg-gradient-secondary' : 'bg-gradient-success' }} mb-0 py-1 px-2" style="font-size:11px;">
                                                                    <i class="fas fa-{{ $p->status === 'checked_in' ? 'undo' : 'check' }}"></i>
                                                                    {{ $p->status === 'checked_in' ? 'Undo' : 'Check-in' }}
                                                                </button>
                                                            </form>
                                                        @endif
                                                        <form method="POST" action="{{ route('open-play.participant.remove', $p->id) }}" data-confirm="Remove {{ $p->display_name }} from Open Play?" class="mb-0">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm bg-gradient-danger mb-0 py-1 px-2" style="font-size:11px;"><i class="fas fa-trash"></i></button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="4" class="text-center text-secondary py-4">No participants registered yet.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-center text-secondary py-4 mb-0">Enable Open Play to manage participants.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== Match Schedule ===== --}}
        @if ($event && $event->matches_generated && count($rounds))
            <div class="row">
                <div class="col-12 mb-4">
                    <div class="card">
                        <div class="card-header pb-0"><h6><i class="fas fa-sitemap me-2 text-warning"></i>Match Scoreboard</h6></div>
                        <div class="card-body">
                            <div class="row">
                                @foreach ($rounds as $round)
                                    <div class="col-lg-4 col-md-6 mb-3">
                                        <div class="border border-radius-lg p-3 h-100 bg-light">
                                            <h6 class="text-sm mb-3"><span class="badge bg-gradient-info">Round {{ $round['round_number'] }}</span></h6>
                                            @foreach ($round['matches'] as $m)
                                                <div class="bg-white border-radius-md p-3 mb-3 border shadow-sm">
                                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                                        <span class="text-xs text-secondary font-weight-bold"><i class="fas fa-map-pin me-1"></i>{{ $m['court'] }}</span>
                                                        @if ($m['winner_team'] !== null)
                                                            <span class="badge bg-gradient-success text-xxs font-weight-bold">COMPLETED</span>
                                                        @else
                                                            <span class="badge bg-gradient-warning text-xxs font-weight-bold">ACTIVE</span>
                                                        @endif
                                                    </div>
                                                    <div class="row align-items-center mb-3">
                                                        <div class="col-5">
                                                            <div class="text-xs {{ (int)$m['winner_team'] === 1 ? 'text-success font-weight-bold' : '' }}">
                                                                <strong>{{ $m['team1'][0] }}</strong><br><strong>{{ $m['team1'][1] }}</strong>
                                                                @if ((int)$m['winner_team'] === 1)
                                                                    <span class="badge bg-gradient-success text-xxs p-1 py-0 ms-1">Winner</span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                        <div class="col-2 text-center">
                                                            <span class="badge bg-gradient-dark text-xxs">VS</span>
                                                        </div>
                                                        <div class="col-5 text-end">
                                                            <div class="text-xs {{ (int)$m['winner_team'] === 2 ? 'text-success font-weight-bold' : '' }}">
                                                                <strong>{{ $m['team2'][0] }}</strong><br><strong>{{ $m['team2'][1] }}</strong>
                                                                @if ((int)$m['winner_team'] === 2)
                                                                    <span class="badge bg-gradient-success text-xxs p-1 py-0 ms-1">Winner</span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="d-flex gap-1 justify-content-center pt-2 border-top">
                                                        <form method="POST" action="{{ route('open-play.submit-result', $m['id']) }}" class="mb-0">
                                                            @csrf
                                                            <input type="hidden" name="winner_team" value="1">
                                                            <button type="submit" class="btn btn-xs {{ (int)$m['winner_team'] === 1 ? 'bg-gradient-success' : 'btn-outline-success' }} py-1 px-2 mb-0" style="font-size:10px;">
                                                                ✓ Team 1 Win
                                                            </button>
                                                        </form>
                                                        <form method="POST" action="{{ route('open-play.submit-result', $m['id']) }}" class="mb-0">
                                                            @csrf
                                                            <input type="hidden" name="winner_team" value="2">
                                                            <button type="submit" class="btn btn-xs {{ (int)$m['winner_team'] === 2 ? 'bg-gradient-success' : 'btn-outline-success' }} py-1 px-2 mb-0" style="font-size:10px;">
                                                                ✓ Team 2 Win
                                                            </button>
                                                        </form>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <script>
        // Real-time slot counter.
        (function () {
            const wrap = document.querySelector('[data-op-slots-url]');
            if (!wrap) return;
            const url = wrap.dataset.opSlotsUrl;
            async function tick() {
                try {
                    const r = await fetch(url, { headers: { 'Accept': 'application/json' } });
                    if (!r.ok) return;
                    const d = await r.json();
                    const taken = document.getElementById('opTaken');
                    const remaining = document.getElementById('opRemaining');
                    const fullLabel = document.getElementById('opFullLabel');
                    const card = document.getElementById('opRemainingCard');
                    if (taken) taken.textContent = d.taken;
                    if (remaining) remaining.textContent = d.remaining;
                    if (fullLabel) fullLabel.textContent = d.is_full ? 'OPEN PLAY FULL' : 'slots left';
                    if (card) {
                        card.classList.toggle('bg-gradient-danger', d.is_full);
                        card.classList.toggle('bg-gradient-success', !d.is_full);
                    }
                } catch (e) {}
            }
            setInterval(tick, 8000);
        })();
    </script>
</x-app-layout>
