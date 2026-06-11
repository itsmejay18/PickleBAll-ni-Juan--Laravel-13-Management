@php
    use Illuminate\Support\Str;

    $cardStyles = [
        ['color' => 'primary', 'change' => '+55%'],
        ['color' => 'dark', 'change' => '+15%'],
        ['color' => 'dark', 'change' => '+90%'],
    ];
@endphp

<x-app-layout>
    <x-slot name="header">{{ $page['title'] }}</x-slot>

    <div class="row">
        <div class="col-lg-8 mb-4">
            <div class="card card-background card-background-mask-primary pbj-readable-hero h-100">
                <div class="full-background" style="background-image: url('{{ asset('soft-ui-dashboard-main/assets/img/curved-images/curved14.jpg') }}')"></div>
                <div class="card-body position-relative z-index-1 p-4">
                    <p class="text-white text-sm text-uppercase font-weight-bold mb-2">{{ $page['eyebrow'] }}</p>
                    <h3 class="text-white font-weight-bolder mb-2">{{ $page['title'] }}</h3>
                    <p class="text-white pbj-readable-copy mb-0">{{ $page['description'] }}</p>
                </div>
            </div>
        </div>

        <div class="col-lg-4 mb-4">
            <div class="card h-100">
                <div class="card-body p-4">
                    <div class="icon icon-shape bg-gradient-primary shadow text-center border-radius-2xl mb-3">
                        <i class="fas {{ $page['icon'] }} text-lg opacity-10" aria-hidden="true"></i>
                    </div>
                    <p class="text-sm mb-1 text-secondary">Owner</p>
                    <h6>{{ $page['owner'] }}</h6>
                    <hr class="horizontal dark">
                    <p class="text-sm mb-1 text-secondary">Status</p>
                    <span class="badge badge-sm bg-gradient-secondary">{{ $page['status'] }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        @foreach ($page['cards'] as $card)
            @php
                $style = $cardStyles[$loop->index] ?? $cardStyles[0];
            @endphp
            <div class="col-lg-4 col-md-6 mb-4">
                <div class="card pbj-stat-card">
                    <span class="mask bg-{{ $style['color'] }} opacity-10 border-radius-lg"></span>
                    <div class="card-body p-3 position-relative">
                        <div class="row">
                            <div class="col-8 text-start">
                                <div class="icon icon-shape bg-white shadow text-center border-radius-2xl">
                                    <i class="fas {{ $card['icon'] ?? 'fa-circle' }} text-dark text-gradient text-lg opacity-10" aria-hidden="true"></i>
                                </div>
                                <h5 class="text-white font-weight-bolder mb-0 mt-3">{{ $card['value'] ?? $card['title'] }}</h5>
                                <p class="text-white text-sm mb-0 mt-1">
                                    @if (isset($card['value']))
                                        {{ $card['title'] }} - {{ $card['text'] }}
                                    @else
                                        {{ $card['text'] }}
                                    @endif
                                </p>
                            </div>
                            <div class="col-4">
                                <div class="dropstart text-end mb-6">
                                    <a href="javascript:;" class="cursor-pointer" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fa fa-ellipsis-h text-white"></i>
                                    </a>
                                    <ul class="dropdown-menu px-2 py-3">
                                        <li><a class="dropdown-item border-radius-md" href="{{ route('dashboard') }}">Dashboard</a></li>
                                        <li><a class="dropdown-item border-radius-md" href="{{ route('modules.show', 'reports') }}">Reports</a></li>
                                    </ul>
                                </div>
                                <p class="text-white text-sm text-end font-weight-bolder mt-auto mb-0">{{ $style['change'] }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- =========================================================
         BOOK COURT (customer)
         ========================================================= --}}
    @if ($module === 'book-court')
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header pb-0">
                        <h6>Create Booking</h6>
                        <p class="text-sm mb-0">Choose your court, date, and time. You'll complete payment on the next step.</p>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('bookings.store') }}" id="bookCourtForm">
                            @csrf

                            {{-- Court & Schedule --}}
                            <div class="row">
                                <div class="col-lg-4 col-md-6 mb-3">
                                    <label class="form-label text-xs">Location</label>
                                    <select name="location_id" id="locationSelect" class="form-control" required>
                                        @foreach (($page['bookingLocations'] ?? []) as $location)
                                            <option value="{{ $location->id }}" @selected((string) old('location_id', request()->query('location_id')) === (string) $location->id)>
                                                {{ $location->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-lg-4 col-md-6 mb-3">
                                    <label class="form-label text-xs">Court</label>
                                    <select name="court_id" id="courtSelect" class="form-control" required>
                                        @foreach (($page['bookingCourts'] ?? []) as $court)
                                            <option value="{{ $court['id'] }}"
                                                data-location-id="{{ $court['location_id'] }}"
                                                data-rate="{{ $court['rate'] }}"
                                                @selected((string) old('court_id', request()->query('court_id')) === (string) $court['id'])>
                                                {{ $court['label'] }} — PHP {{ number_format($court['rate'], 2) }}/hr
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-lg-4 col-md-6 mb-3">
                                    <label class="form-label text-xs">Date</label>
                                    <input type="date" name="reservation_date" id="reservationDate" class="form-control"
                                         value="{{ old('reservation_date', request()->query('date', now()->addDay()->toDateString())) }}"
                                         min="{{ now()->toDateString() }}" required>
                                </div>

                                <div class="col-lg-2 col-md-6 mb-3">
                                    <label class="form-label text-xs">Start</label>
                                    <input type="time" name="start_time" id="startTime" class="form-control"
                                        value="{{ old('start_time', request()->query('start_time', '08:00')) }}" required>
                                </div>

                                <div class="col-lg-2 col-md-6 mb-3">
                                    <label class="form-label text-xs">End</label>
                                    <input type="time" name="end_time" id="endTime" class="form-control"
                                        value="{{ old('end_time', request()->query('end_time', '10:00')) }}" required>
                                </div>

                                <div class="col-lg-8 col-md-12 mb-3">
                                    <label class="form-label text-xs">Special requests</label>
                                    <input type="text" name="special_requests" class="form-control"
                                        value="{{ old('special_requests') }}" placeholder="Optional note for the court team">
                                </div>
                            </div>

                            {{-- Equipment rentals --}}
                            <div class="row">
                                @forelse (($page['bookingEquipment'] ?? []) as $item)
                                    <div class="col-lg-4 col-md-6 mb-3">
                                        <label class="form-label text-xs">{{ $item['name'] }}</label>
                                        <div class="input-group">
                                            <span class="input-group-text">Qty</span>
                                            <input type="number"
                                                name="equipment[{{ $item['id'] }}]"
                                                class="form-control equipment-qty"
                                                data-price="{{ $item['price'] }}"
                                                min="0"
                                                max="{{ min($item['max'], $item['available']) }}"
                                                value="{{ old('equipment.'.$item['id'], 0) }}">
                                        </div>
                                        <p class="text-xs text-secondary mb-0 mt-1">
                                            {{ $item['available'] }} available — PHP {{ number_format($item['price'], 2) }}/unit
                                            @if ($item['deposit'] > 0)
                                                — PHP {{ number_format($item['deposit'], 2) }} deposit
                                            @endif
                                        </p>
                                    </div>
                                @empty
                                    <div class="col-12">
                                        <p class="text-sm text-secondary mb-3">No rentable equipment available yet.</p>
                                    </div>
                                @endforelse
                            </div>

                            <hr class="horizontal dark my-3">

                            {{-- Live total + submit --}}
                            <style>
                                @media (max-width: 576px) {
                                    .pbj-booking-footer {
                                        flex-direction: column;
                                        align-items: stretch !important;
                                        text-align: center;
                                        gap: 1.25rem !important;
                                    }
                                    .pbj-booking-footer button {
                                        width: 100%;
                                    }
                                }
                            </style>
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 pbj-booking-footer">
                                <div>
                                    <p class="text-xs text-secondary mb-1">Estimated Total</p>
                                    <h4 class="font-weight-bolder mb-0 text-dark" id="totalAmountDisplay">PHP 0.00</h4>
                                    <p class="text-xs text-secondary mb-0" id="totalBreakdown">Select court &amp; time to calculate</p>
                                </div>
                                <button type="submit" class="btn bg-gradient-info mb-0 px-4">
                                    <i class="fas fa-arrow-right me-2"></i>Continue to Payment
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <script>
        (function () {
            const locationSelect = document.getElementById('locationSelect');
            const courtSelect = document.getElementById('courtSelect');
            const allCourtOptions = Array.from(courtSelect.options);

            function filterCourts() {
                if (!locationSelect || !courtSelect) return;
                const selectedLocId = locationSelect.value;
                const currentVal = courtSelect.value;
                
                courtSelect.innerHTML = '';
                
                const matched = allCourtOptions.filter(opt => opt.dataset.locationId === selectedLocId);
                matched.forEach(opt => courtSelect.appendChild(opt));
                
                if (matched.length > 0) {
                    if (matched.some(opt => opt.value === currentVal)) {
                        courtSelect.value = currentVal;
                    } else {
                        courtSelect.value = matched[0].value;
                    }
                }
                
                calcTotal();
            }

            function calcTotal() {
                const courtSelect = document.getElementById('courtSelect');
                const startTime   = document.getElementById('startTime').value;
                const endTime     = document.getElementById('endTime').value;
                if (!courtSelect || !startTime || !endTime) return;

                const rate = parseFloat(courtSelect.options[courtSelect.selectedIndex]?.dataset?.rate || 0);
                const [sh, sm] = startTime.split(':').map(Number);
                const [eh, em] = endTime.split(':').map(Number);
                const hours = Math.max(0, (eh * 60 + em - sh * 60 - sm) / 60);

                let courtTotal = Math.round(rate * hours * 100) / 100;
                let eqTotal = 0;
                document.querySelectorAll('.equipment-qty').forEach(function (inp) {
                    eqTotal += (parseInt(inp.value) || 0) * (parseFloat(inp.dataset.price) || 0);
                });
                eqTotal = Math.round(eqTotal * 100) / 100;
                const grand = Math.round((courtTotal + eqTotal) * 100) / 100;

                document.getElementById('totalAmountDisplay').textContent =
                    'PHP ' + grand.toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2});

                const parts = [];
                if (courtTotal > 0) parts.push('Court PHP ' + courtTotal.toLocaleString('en-PH', {minimumFractionDigits:2}));
                if (eqTotal > 0)    parts.push('Equipment PHP ' + eqTotal.toLocaleString('en-PH', {minimumFractionDigits:2}));
                document.getElementById('totalBreakdown').textContent =
                    parts.length ? parts.join(' + ') : (hours <= 0 ? 'End time must be after start time' : 'Select court & time to calculate');
            }

            ['courtSelect','startTime','endTime'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.addEventListener('change', calcTotal);
            });
            if (locationSelect) {
                locationSelect.addEventListener('change', filterCourts);
            }
            document.querySelectorAll('.equipment-qty').forEach(el => el.addEventListener('input', calcTotal));
            
            // Initial call to set up the courts matching the selected location
            filterCourts();
        })();
        </script>
    @endif

    {{-- =========================================================
         LOCATIONS (admin) - modal-driven CRUD
         ========================================================= --}}
    @if ($module === 'locations' && ($page['canManageLocations'] ?? false))
        @include('modules.partials.locations', ['locations' => $page['manageableLocations'] ?? []])
    @endif

    {{-- =========================================================
         COURTS (admin) - modal-driven CRUD
         ========================================================= --}}
    @if ($module === 'courts' && ($page['canManageOperations'] ?? false))
        @include('modules.partials.courts', [
            'courts' => $page['manageableCourts'] ?? [],
            'locationOptions' => $page['locations'] ?? [],
        ])
    @endif

    {{-- =========================================================
         EQUIPMENT (admin) - modal-driven CRUD
         ========================================================= --}}
    @if ($module === 'equipment' && ($page['canManageOperations'] ?? false))
        @include('modules.partials.equipment', [
            'equipment' => $page['manageableEquipment'] ?? [],
            'locationOptions' => $page['locations'] ?? [],
        ])
    @endif

    {{-- =========================================================
         REPORTS (admin) - export buttons
         ========================================================= --}}
    @if ($module === 'reports' && auth()->user()?->hasAnyRole(['super_admin','admin']))
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header pb-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <h6 class="mb-0">Export reports to CSV</h6>
                            <p class="text-sm mb-0">Pick a date range, then download. Defaults to the current calendar month.</p>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="{{ url('/reports/export/revenue') }}" class="row g-2 align-items-end" id="reportExportForm">
                            <div class="col-md-3">
                                <label class="form-label text-xs">From</label>
                                <input type="date" name="from" class="form-control" value="{{ now()->startOfMonth()->toDateString() }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label text-xs">To</label>
                                <input type="date" name="to" class="form-control" value="{{ now()->endOfMonth()->toDateString() }}">
                            </div>
                            <div class="col-md-6 d-flex flex-wrap gap-2">
                                @foreach (['revenue' => 'Revenue', 'bookings' => 'Bookings', 'cancellations' => 'Cancellations', 'no-shows' => 'No-shows', 'equipment' => 'Equipment usage'] as $type => $label)
                                    <button type="button" class="btn btn-outline-info mb-0"
                                        onclick="document.getElementById('reportExportForm').action='{{ url('/reports/export/'.$type) }}';document.getElementById('reportExportForm').submit();">
                                        <i class="fas fa-file-csv me-1"></i>{{ $label }}
                                    </button>
                                @endforeach
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- =========================================================
         REVIEWS (admin) - moderation actions
         ========================================================= --}}
    @if ($module === 'reviews' && auth()->user()?->hasAnyRole(['super_admin','admin']))
        @include('modules.partials.reviews-moderation', ['ratings' => $page['moderatableRatings'] ?? []])
    @endif

    {{-- =========================================================
         INCOME (super_admin) - monthly revenue and customer list
         ========================================================= --}}
    @if ($module === 'income' && auth()->user()?->hasRole('super_admin'))
        @include('modules.partials.income', [
            'incomeMonth' => $page['incomeMonth'] ?? now()->format('F Y'),
            'incomeStart' => $page['incomeStart'] ?? now()->startOfMonth()->toDateString(),
            'incomeEnd' => $page['incomeEnd'] ?? now()->endOfMonth()->toDateString(),
            'monthlyIncome' => $page['monthlyIncome'] ?? 0,
            'totalBookings' => $page['totalBookings'] ?? 0,
            'incomeCustomers' => $page['incomeCustomers'] ?? [],
        ])
    @endif

    {{-- =========================================================
         USERS (admin) - modal-driven CRUD (L5, A3)
         ========================================================= --}}
    @if ($module === 'users' && auth()->user()?->hasAnyRole(['super_admin','admin']))
        @include('modules.partials.users', [
            'users' => $page['manageableUsers'] ?? [],
            'locationOptions' => $page['manageableLocations'] ?? [],
            'isSuperAdmin' => $page['isSuperAdmin'] ?? false,
        ])
    @endif

    {{-- =========================================================
         RESCHEDULE MANAGEMENT (admin) - lock/unlock actions
         ========================================================= --}}
    @if ($module === 'reschedule-management' && auth()->user()?->hasAnyRole(['super_admin','admin']))
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header pb-0">
                        <h6>Reschedule Lock Management</h6>
                        <p class="text-sm mb-0">Lock or unlock rescheduling for active reservations. Use this when courts are unavailable (rain, maintenance, special events).</p>
                    </div>
                    <div class="card-body">
                        @if (!empty($page['rows']))
                            <div class="table-responsive">
                                <table class="table align-items-center mb-0">
                                    <thead>
                                        <tr>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Reservation</th>
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Details</th>
                                            <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Status</th>
                                            <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Reason</th>
                                            <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($page['rows'] as $row)
                                            @php
                                                $isLocked = ($row['status'] ?? '') === 'Locked';
                                                $noteText = $row['note'] ?? '';
                                                $lockReason = '';
                                                if (str_contains($noteText, ' | Locked: ')) {
                                                    $parts = explode(' | Locked: ', $noteText);
                                                    $lockReason = end($parts);
                                                }
                                                $details = trim(str_replace(' | Locked: ' . $lockReason, '', $noteText));
                                            @endphp
                                            <tr>
                                                <td>
                                                    <div class="d-flex px-2 py-1">
                                                        <div class="avatar avatar-sm bg-gradient-info me-3 d-flex align-items-center justify-content-center">
                                                            <i class="fas fa-calendar-check text-white text-sm"></i>
                                                        </div>
                                                        <div class="d-flex flex-column justify-content-center">
                                                            <h6 class="mb-0 text-sm font-weight-bold">{{ $row['feature'] }}</h6>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="text-sm">
                                                    {{ Str::limit($details, 80) }}
                                                </td>
                                                <td class="align-middle text-center text-sm">
                                                    @if ($isLocked)
                                                        <span class="badge badge-sm bg-gradient-danger">
                                                            <i class="fas fa-lock me-1"></i>Locked
                                                        </span>
                                                    @else
                                                        <span class="badge badge-sm bg-gradient-success">
                                                            <i class="fas fa-unlock me-1"></i>Unlocked
                                                        </span>
                                                    @endif
                                                </td>
                                                <td class="align-middle text-center text-xs">
                                                    @if ($lockReason)
                                                        <span class="text-danger" title="{{ $lockReason }}">
                                                            {{ Str::limit($lockReason, 40) }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted">—</span>
                                                    @endif
                                                </td>
                                                <td class="align-middle text-center">
                                                    @if ($isLocked)
                                                        <form method="POST" action="{{ route('reservations.reschedule.unlock', $row['reservation_id']) }}" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm btn-outline-success">Unlock</button>
                                                        </form>
                                                    @else
                                                        <form method="POST" action="{{ route('reservations.reschedule.lock', $row['reservation_id']) }}" class="d-inline" onsubmit="const reason=prompt('Enter the lock reason for this reservation (e.g. rain, maintenance)'); if (!reason) { return false; } this.reason.value = reason;">
                                                            @csrf
                                                            <input type="hidden" name="reason" value="">
                                                            <button type="submit" class="btn btn-sm btn-outline-warning">Lock</button>
                                                        </form>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-sm text-secondary mb-0">No active reservations to manage.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- =========================================================
         PAYMENTS (customer + reviewer)
         ========================================================= --}}
    @if ($module === 'payments')
        <div class="row mb-4">
            @if (! ($page['canReviewPayments'] ?? false))
                <div class="col-12">
                    <div class="card">
                        <div class="card-header pb-0">
                            <h6>Upload GCash Payment Screenshot</h6>
                            <p class="text-sm mb-0">Send payment to <span class="font-weight-bold">{{ $page['ownerGcashNumber'] ?? '09123456789' }}</span>, then upload the screenshot photo and reference number.</p>
                        </div>
                        <div class="card-body">
                            @forelse (($page['paymentUploads'] ?? []) as $reservation)
                                <form method="POST" action="{{ route('payments.proof.store') }}" enctype="multipart/form-data" class="border rounded p-3 mb-3">
                                    @csrf
                                    <input type="hidden" name="reservation_id" value="{{ $reservation['id'] }}">

                                    <div class="row align-items-end">
                                        <div class="col-lg-3 col-md-6 mb-3">
                                            <p class="text-xs text-secondary mb-1">Reservation</p>
                                            <h6 class="mb-0">{{ $reservation['reservation_code'] }}</h6>
                                            <p class="text-xs text-secondary mb-0">{{ $reservation['court'] }}</p>
                                            <p class="text-xs text-secondary mb-0">{{ $reservation['schedule'] }}</p>
                                        </div>
                                        <div class="col-lg-2 col-md-6 mb-3">
                                            <p class="text-xs text-secondary mb-1">Amount</p>
                                            <h6 class="mb-0">{{ $reservation['amount'] }}</h6>
                                            <span class="badge badge-sm bg-gradient-warning">{{ $reservation['status'] }}</span>
                                        </div>
                                        <div class="col-lg-2 col-md-6 mb-3">
                                            <label class="form-label text-xs">GCash reference</label>
                                            <input type="text" name="gcash_reference_number" class="form-control" value="{{ old('gcash_reference_number', $reservation['gcash_reference_number']) }}" required>
                                        </div>
                                        <div class="col-lg-2 col-md-6 mb-3">
                                            <label class="form-label text-xs">Sender number</label>
                                            <input type="text" name="gcash_sender_number" class="form-control" value="{{ old('gcash_sender_number', auth()->user()->mobile_number) }}">
                                        </div>
                                        <div class="col-lg-2 col-md-8 mb-3">
                                            <label class="form-label text-xs">Screenshot photo</label>
                                            <input type="file" name="gcash_screenshot" class="form-control" accept="image/png,image/jpeg,image/webp" required>
                                        </div>
                                        <div class="col-lg-1 col-md-4 mb-3 text-end">
                                            <button type="submit" class="btn bg-gradient-primary mb-0 w-100">Upload</button>
                                        </div>
                                    </div>

                                    @if (! empty($reservation['rejection_reason']))
                                        <p class="text-xs text-danger mb-0">Rejected before: {{ $reservation['rejection_reason'] }}</p>
                                    @endif
                                </form>
                            @empty
                                <p class="text-sm mb-0">No reservation is waiting for a GCash screenshot right now.</p>
                            @endforelse
                        </div>
                    </div>
            @else
                @if (auth()->user()->hasAnyRole(['super_admin', 'admin']))
                    <div class="col-12 mb-4">
                        <div class="card">
                            <div class="card-header pb-0">
                                <h6>GCash Settings</h6>
                                <p class="text-sm mb-0">Update the GCash number and QR code image shown to customers at checkout.</p>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="{{ route('admin.settings.gcash.update') }}" enctype="multipart/form-data">
                                    @csrf
                                    <div class="row align-items-end">
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label text-xs">GCash Number</label>
                                            <input type="text" name="owner_gcash_number" class="form-control" value="{{ $page['ownerGcashNumber'] ?? '09123456789' }}" required>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <label class="form-label text-xs">GCash QR Code Image (JPG, PNG, WEBP)</label>
                                            <input type="file" name="owner_gcash_qr" class="form-control" accept="image/png,image/jpeg,image/webp">
                                        </div>
                                        <div class="col-md-2 mb-3 text-center">
                                            @if (!empty($page['ownerGcashQr']))
                                                <div class="text-xs text-secondary mb-1">Current QR:</div>
                                                <a href="{{ asset($page['ownerGcashQr']) }}" target="_blank">
                                                    <img src="{{ asset($page['ownerGcashQr']) }}" alt="GCash QR Code" class="img-fluid border-radius-md shadow-sm" style="max-height: 50px;">
                                                </a>
                                            @else
                                                <span class="badge bg-light text-dark">No QR Code set</span>
                                            @endif
                                        </div>
                                        <div class="col-md-2 mb-3 text-end">
                                            <button type="submit" class="btn bg-gradient-info mb-0 w-100">Save Settings</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="col-12">
                    <div class="card">
                        <div class="card-header pb-0">
                            <h6>GCash Screenshot Review Queue</h6>
                            <p class="text-sm mb-0">Open each uploaded photo, match the amount and reference number, then approve or reject.</p>
                        </div>
                        <div class="card-body">
                            @forelse (($page['paymentReviews'] ?? []) as $payment)
                                <div class="border rounded p-3 mb-3">
                                    <div class="row">
                                        <div class="col-lg-3 col-md-6 mb-3">
                                            <p class="text-xs text-secondary mb-1">Payment</p>
                                            <h6 class="mb-0">{{ $payment['payment_reference'] }}</h6>
                                            <p class="text-xs text-secondary mb-0">{{ $payment['customer'] }}</p>
                                            <p class="text-xs text-secondary mb-0">{{ $payment['created_at'] }}</p>
                                        </div>
                                        <div class="col-lg-3 col-md-6 mb-3">
                                            <p class="text-xs text-secondary mb-1">Reservation</p>
                                            <h6 class="mb-0">{{ $payment['reservation_code'] }}</h6>
                                            <p class="text-xs text-secondary mb-0">{{ $payment['court'] }}</p>
                                            <p class="text-xs text-secondary mb-0">{{ $payment['schedule'] }}</p>
                                        </div>
                                        <div class="col-lg-2 col-md-6 mb-3">
                                            <p class="text-xs text-secondary mb-1">GCash details</p>
                                            <h6 class="mb-0">{{ $payment['amount'] }}</h6>
                                            <p class="text-xs text-secondary mb-0">Ref: {{ $payment['gcash_reference_number'] }}</p>
                                            <p class="text-xs text-secondary mb-0">From: {{ $payment['gcash_sender_number'] ?: 'Not provided' }}</p>
                                        </div>
                                        <div class="col-lg-2 col-md-6 mb-3">
                                            @if ($payment['proof_url'])
                                                <a href="{{ $payment['proof_url'] }}" target="_blank">
                                                    <img src="{{ $payment['proof_url'] }}" class="img-fluid border-radius-lg shadow-sm" alt="GCash payment screenshot">
                                                </a>
                                            @else
                                                <span class="badge badge-sm bg-gradient-danger">No photo found</span>
                                            @endif
                                        </div>
                                        <div class="col-lg-2 mb-3">
                                            <form method="POST" action="{{ route('payments.approve', $payment['id']) }}" class="mb-2">
                                                @csrf
                                                <button type="submit" class="btn bg-gradient-success w-100 mb-0">Approve</button>
                                            </form>
                                            <form method="POST" action="{{ route('payments.reject', $payment['id']) }}">
                                                @csrf
                                                <textarea name="rejection_reason" class="form-control mb-2" rows="2" placeholder="Reason" required></textarea>
                                                <button type="submit" class="btn bg-gradient-danger w-100 mb-0">Reject</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <p class="text-sm mb-0">No uploaded GCash screenshots are waiting for review.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @endif

    {{-- =========================================================
         BOOK HISTORY (customer / admin / staff list)
         ========================================================= --}}
    @if ($module === 'receipts')
        <div class="row mb-4">
            <div class="col-12">
                <div class="card shadow-lg border-0 bg-white" style="border-radius: 1rem; overflow: hidden;">
                    <div class="card-header pb-2 bg-gradient-dark position-relative z-index-1">
                        <div class="row align-items-center">
                            <div class="col-sm-8">
                                <h5 class="text-white font-weight-bolder mb-1">
                                    <i class="fas fa-history me-2 text-warning"></i>Book History Store
                                </h5>
                                <p class="text-white text-xs opacity-8 mb-0">
                                    All your approved court bookings, equipment rentals, and receipts are stored here.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="card-body px-0 pt-0 pb-2">
                        <div class="table-responsive">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Reservation Code</th>
                                        @if (auth()->user()->hasAnyRole(['super_admin', 'admin', 'location_manager', 'staff']))
                                            <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Customer</th>
                                        @endif
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Location &amp; Court</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Schedule</th>
                                        <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Amount Paid</th>
                                        <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Status</th>
                                        <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse (($page['receiptsList'] ?? []) as $receipt)
                                        <tr class="hover-shadow-sm transition-all">
                                            <td>
                                                <div class="d-flex px-3 py-2">
                                                    <div class="icon icon-shape bg-gradient-info text-center border-radius-md me-3 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                                                        <i class="fas fa-receipt text-white text-sm"></i>
                                                    </div>
                                                    <div class="d-flex flex-column justify-content-center">
                                                        <h6 class="mb-0 text-sm font-weight-bold text-dark">{{ $receipt['reservation_code'] }}</h6>
                                                        <p class="text-xxs text-secondary mb-0">Ref: {{ $receipt['reference'] }}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            @if (auth()->user()->hasAnyRole(['super_admin', 'admin', 'location_manager', 'staff']))
                                                <td>
                                                    <div class="d-flex align-items-center py-1">
                                                        <img src="{{ $receipt['photo_url'] }}" class="avatar avatar-xs rounded-circle me-2" alt="{{ $receipt['customer'] }}">
                                                        <span class="text-xs font-weight-bold text-dark">{{ $receipt['customer'] }}</span>
                                                    </div>
                                                </td>
                                            @endif
                                            <td>
                                                <span class="text-xs font-weight-bold text-dark"><i class="fas fa-map-marker-alt me-1 text-secondary"></i>{{ $receipt['court'] }}</span>
                                            </td>
                                            <td>
                                                <span class="text-xs text-secondary"><i class="far fa-clock me-1"></i>{{ $receipt['schedule'] }}</span>
                                            </td>
                                            <td class="align-middle text-center">
                                                <span class="text-sm font-weight-bolder text-dark">{{ $receipt['amount'] }}</span>
                                            </td>
                                            <td class="align-middle text-center text-sm">
                                                <span class="badge badge-sm bg-gradient-{{ $receipt['color'] }} px-2 py-1" style="border-radius: 0.5rem;">
                                                    {{ $receipt['status'] }}
                                                </span>
                                            </td>
                                            <td class="align-middle text-center">
                                                <a href="{{ route('receipts.show', $receipt['reservation_id']) }}" class="btn btn-sm bg-gradient-dark mb-0 py-1.5 px-3 border-radius-md" style="font-size: 11px;">
                                                    <i class="fas fa-eye me-1"></i>View Receipt
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center py-5 text-secondary">
                                                <i class="fas fa-receipt fa-2x mb-3 d-block opacity-4"></i>
                                                <span class="text-sm">No approved bookings found in history.</span>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- =========================================================
         Generic feature/owner table - rendered for all module pages
         ========================================================= --}}
    @if ($module !== 'receipts')
        <div class="row">
            <div class="col-lg-8 col-md-7 mb-md-0 mb-4">
                <div class="card">
                    <div class="card-header pb-0">
                        <div class="row">
                            <div class="col-lg-6 col-7">
                                <h6>{{ $page['title'] }} Map</h6>
                                <p class="text-sm mb-0">
                                    <i class="fa fa-check text-info" aria-hidden="true"></i>
                                    <span class="font-weight-bold ms-1">{{ count($page['rows']) }} records</span> live
                                </p>
                            </div>
                            <div class="col-lg-6 col-5 my-auto text-end">
                                <div class="dropdown float-lg-end pe-4">
                                    <a class="cursor-pointer" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fa fa-ellipsis-v text-secondary"></i>
                                    </a>
                                    <ul class="dropdown-menu px-2 py-3 ms-sm-n4 ms-n5">
                                        <li><a class="dropdown-item border-radius-md" href="{{ route('dashboard') }}">Back to dashboard</a></li>
                                        <li><a class="dropdown-item border-radius-md" href="{{ route('profile.edit') }}">Account profile</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="card-body px-0 pb-2">
                        <div class="table-responsive">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Feature</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Owner</th>
                                        <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Status</th>
                                        <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Completion</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($page['rows'] as $row)
                                        @php
                                            $progress = $row['progress'] ?? [25, 40, 60, 100][$loop->index % 4];
                                        @endphp
                                        <tr>
                                            <td>
                                                <div class="d-flex px-2 py-1">
                                                    <div class="avatar avatar-sm bg-gradient-info me-3 d-flex align-items-center justify-content-center">
                                                        <i class="fas {{ $row['icon'] ?? $page['icon'] ?? 'fa-list' }} text-white text-sm"></i>
                                                    </div>
                                                    <div class="d-flex flex-column justify-content-center">
                                                        <h6 class="mb-0 text-sm">{{ $row['feature'] }}</h6>
                                                        <p class="text-xs text-secondary mb-0">{{ $row['note'] }}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                @php
                                                    $owners = count($row['owners'] ?? []) ? $row['owners'] : [['name' => $row['objective'] ?? $page['owner'], 'photo_url' => asset('images/branding.png')]];
                                                @endphp
                                                <div class="avatar-group mt-2">
                                                    @foreach ($owners as $owner)
                                                        <a href="javascript:;" class="avatar avatar-xs rounded-circle" data-bs-toggle="tooltip" data-bs-placement="bottom" title="{{ $owner['name'] }}">
                                                            <img src="{{ $owner['photo_url'] }}" alt="{{ $owner['name'] }}">
                                                        </a>
                                                    @endforeach
                                                </div>
                                            </td>
                                            <td class="align-middle text-center text-sm">
                                                <span class="badge badge-sm bg-gradient-{{ $row['color'] ?? 'secondary' }}">{{ $row['status'] ?? $page['status'] }}</span>
                                            </td>
                                            <td class="align-middle">
                                                <div class="progress-wrapper w-75 mx-auto">
                                                    <div class="progress-info">
                                                        <div class="progress-percentage">
                                                            <span class="text-xs font-weight-bold">{{ $progress }}%</span>
                                                        </div>
                                                    </div>
                                                    <div class="progress">
                                                        <div class="progress-bar bg-gradient-{{ ($row['color'] ?? null) === 'warning' ? 'warning' : ($progress === 100 ? 'success' : 'info') }}" style="width: {{ $progress }}%" role="progressbar" aria-valuenow="{{ $progress }}" aria-valuemin="0" aria-valuemax="100"></div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 col-md-5">
                <div class="card h-100">
                    <div class="card-header pb-0">
                        <h6>Orders overview</h6>
                        <p class="text-sm">
                            <i class="fa fa-arrow-up text-success" aria-hidden="true"></i>
                            <span class="font-weight-bold">Live</span> module outline
                        </p>
                    </div>
                    <div class="card-body p-3">
                        <div class="timeline timeline-one-side">
                            @foreach ($page['rows'] as $row)
                                <div class="timeline-block {{ $loop->last ? '' : 'mb-3' }}">
                                    <span class="timeline-step">
                                        <i class="fa fa-check text-{{ $loop->first ? 'success' : ($loop->iteration === 2 ? 'info' : 'dark') }} text-gradient"></i>
                                    </span>
                                    <div class="timeline-content">
                                        <h6 class="text-dark text-sm font-weight-bold mb-0">{{ $row['feature'] }}</h6>
                                        <p class="text-secondary font-weight-bold text-xs mt-1 mb-0">{{ $row['note'] }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</x-app-layout>
