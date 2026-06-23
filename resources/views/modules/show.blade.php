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
        <div class="col-12 mb-4">
            <div class="card card-background card-background-mask-primary pbj-readable-hero">
                <div class="full-background" style="background-image: url('{{ asset('soft-ui-dashboard-main/assets/img/curved-images/curved14.jpg') }}')"></div>
                <div class="card-body position-relative z-index-1 p-4">
                    <p class="text-white text-sm text-uppercase font-weight-bold mb-2">{{ $page['eyebrow'] }}</p>
                    <h3 class="text-white font-weight-bolder mb-2">{{ $page['title'] }}</h3>
                    <p class="text-white pbj-readable-copy mb-0">{{ $page['description'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        @foreach ($page['cards'] as $card)
            @php
                $style = $cardStyles[$loop->index] ?? $cardStyles[0];
            @endphp
            <div class="col-6 col-lg-4 col-md-6 mb-4">
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
        <style>
            .slots-timeline {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
                gap: 0.5rem;
                width: 100%;
                margin-top: 0.5rem;
            }
            @media (max-width: 500px) {
                .slots-timeline {
                    grid-template-columns: repeat(2, 1fr) !important;
                }
            }
            .slot-badge {
                border-radius: 8px;
                padding: 0.5rem;
                text-align: center;
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 0.35rem;
                width: 100%;
                transition: all 0.2s ease-in-out;
            }
            /* Available Slot styling */
            .slot-badge.slot-hover {
                background-color: rgba(45, 206, 137, 0.08);
                border: 1px solid rgba(45, 206, 137, 0.25);
            }
            .slot-badge.slot-hover:hover {
                background-color: rgba(45, 206, 137, 0.18) !important;
                border-color: #2dce89 !important;
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(45, 206, 137, 0.15);
                cursor: pointer;
            }
            .slot-badge.slot-hover .slot-label {
                color: #2dce89;
                font-weight: 700;
                font-size: 0.75rem;
            }
            .slot-badge .btn-slot-action {
                padding: 0.25rem 0.5rem;
                font-size: 0.65rem;
                border-radius: 4px;
                font-weight: 700;
                width: 100%;
                text-transform: uppercase;
                letter-spacing: 0.05em;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 0.2rem;
                margin: 0;
                border: none;
            }
            .slot-badge .btn-slot-action.btn-book {
                background-color: #2dce89;
                color: #fff;
            }
            .slot-badge.slot-hover:hover .btn-slot-action.btn-book {
                background-color: #2dce89;
                box-shadow: 0 2px 4px rgba(45, 206, 137, 0.2);
            }
            
            /* Unavailable Slot styling - RED */
            .slot-badge.slot-unavailable {
                background-color: rgba(245, 54, 92, 0.08) !important;
                border: 1px solid rgba(245, 54, 92, 0.25) !important;
                cursor: not-allowed;
            }
            .slot-badge.slot-unavailable .slot-label {
                color: #f5365c;
                font-weight: 700;
                font-size: 0.75rem;
                text-decoration: line-through;
            }
            .slot-badge .btn-slot-action.btn-blocked {
                background-color: #f5365c;
                color: #fff;
                font-size: 0.6rem;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
            
            /* Ongoing Payment Slot styling - ORANGE/YELLOW */
            .slot-badge.slot-ongoing-payment {
                background-color: rgba(251, 191, 36, 0.08) !important;
                border: 1px solid rgba(251, 191, 36, 0.3) !important;
                cursor: not-allowed;
                min-width: 125px !important;
            }
            .slot-badge.slot-ongoing-payment .slot-label {
                color: #d97706;
                font-weight: 700;
                font-size: 0.75rem;
            }
            .slot-badge .btn-slot-action.btn-ongoing {
                background-color: #f59e0b;
                color: #fff;
                font-size: 0.6rem;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
        </style>
        <div class="row mb-4">
            <!-- Left Column: Live Availability Grid -->
            <div class="col-lg-7 col-md-12 mb-4">
                <div class="card shadow-lg h-100">
                    <div class="card-header pb-0">
                        <h6>Live Availability Grid</h6>
                        <p class="text-xs text-secondary mb-0">Select a branch and date to see live court slots. Click an open slot to book.</p>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label text-xxs text-uppercase font-weight-bolder text-secondary">Branch</label>
                                <select id="grid-location-select" class="form-control form-control-sm">
                                    @foreach (($page['bookingLocations'] ?? []) as $location)
                                        <option value="{{ $location->id }}" @selected((string) old('location_id', request()->query('location_id')) === (string) $location->id)>
                                            {{ $location->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label text-xxs text-uppercase font-weight-bolder text-secondary">Date</label>
                                <input type="date" id="grid-date-picker" class="form-control form-control-sm" 
                                       value="{{ old('reservation_date', request()->query('date', now()->addDay()->toDateString())) }}" 
                                       min="{{ now()->toDateString() }}">
                            </div>
                        </div>

                        <!-- Slots List group -->
                        <div id="grid-availability-timeline" class="border rounded p-2" style="max-height: 450px; overflow-y: auto; background: #fafafa;">
                            <div class="text-center py-4 text-secondary">
                                <i class="fas fa-spinner fa-spin me-2"></i>Loading slots...
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Create Booking Form -->
            <div class="col-lg-5 col-md-12 mb-4">
                <div class="card shadow-lg h-100">
                    <div class="card-header pb-0">
                        <h6>Create Booking</h6>
                        <p class="text-sm mb-0">Choose your court, date, and time. You'll complete payment on the next step.</p>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('bookings.store') }}" id="bookCourtForm">
                            @csrf

                            {{-- Court & Schedule --}}
                            <div class="row">
                                <div class="col-12 mb-3">
                                    <label class="form-label text-xs">Location</label>
                                    <select name="location_id" id="locationSelect" class="form-control" required>
                                        @foreach (($page['bookingLocations'] ?? []) as $location)
                                            <option value="{{ $location->id }}" @selected((string) old('location_id', request()->query('location_id')) === (string) $location->id)>
                                                {{ $location->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12 mb-3">
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

                                <div class="col-sm-4 col-12 mb-3">
                                    <label class="form-label text-xs">Date</label>
                                    <input type="date" name="reservation_date" id="reservationDate" class="form-control"
                                         value="{{ old('reservation_date', request()->query('date', now()->addDay()->toDateString())) }}"
                                         min="{{ now()->toDateString() }}" required>
                                </div>

                                <div class="col-sm-4 col-6 mb-3">
                                    <label class="form-label text-xs">Start Time</label>
                                    <input type="time" name="start_time" id="startTime" class="form-control"
                                         value="{{ old('start_time', request()->query('start_time', '08:00')) }}" required>
                                </div>

                                <div class="col-sm-4 col-6 mb-3">
                                    <label class="form-label text-xs">Duration</label>
                                    <select id="durationHours" class="form-control" required>
                                        <option value="1">1 Hour</option>
                                        <option value="2">2 Hours</option>
                                        <option value="3">3 Hours</option>
                                        <option value="4">4 Hours</option>
                                    </select>
                                    <input type="hidden" name="end_time" id="endTime" value="{{ old('end_time', request()->query('end_time', '09:00')) }}">
                                </div>

                                <div class="col-12 mb-3">
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

        <!-- Terms and Conditions Modal -->
        <div id="pbjTermsModal" class="pbj-confirm-overlay" style="display:none; position:fixed; inset:0; z-index:1080; background:rgba(15,23,42,0.65); align-items:center; justify-content:center; padding:1rem;">
            <div class="card shadow-lg border-0" style="max-width:550px; width:100%; border-radius:1rem;">
                <div class="card-body p-4 text-start">
                    <div class="d-flex align-items-center mb-3">
                        <div class="icon icon-shape bg-gradient-info text-white rounded-circle me-3 d-flex align-items-center justify-content-center flex-shrink-0" style="width:48px; height:48px;">
                            <i class="fas fa-file-contract text-lg"></i>
                        </div>
                        <div>
                            <h5 class="font-weight-bolder text-dark mb-0">Booking Terms & Conditions</h5>
                            <p class="text-xs text-secondary mb-0">Please review and agree to proceed</p>
                        </div>
                    </div>
                    
                    <div class="border rounded p-3 bg-light mb-3" style="max-height:220px; overflow-y:auto; font-size:0.875rem; line-height:1.5; color:#495057; white-space: pre-wrap;">{{ $page['publicSiteSettings']['booking_terms_and_conditions'] ?? "IMPORTANT: No refunds will be issued under any circumstances unless court operations are suspended due to inclement weather (e.g. rain). Please ensure you send the exact GCash amount including decimal fractions. Failures to do so will result in booking cancellation without refund." }}</div>

                    <div class="form-check text-start mb-4">
                        <input class="form-check-input" type="checkbox" id="agreeTermsCheckbox">
                        <label class="form-check-label text-sm text-dark font-weight-bold mb-0" for="agreeTermsCheckbox">
                            I have read and agree to the terms, refund policy, and GCash exact amount rules.
                        </label>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" id="closeTermsBtn" class="btn bg-gradient-secondary mb-0 px-4">Cancel</button>
                        <button type="button" id="submitBookingBtn" class="btn bg-gradient-info mb-0 px-4" disabled>Accept &amp; Book</button>
                    </div>
                </div>
            </div>
        </div>

        <script>
        (function () {
            const locationSelect = document.getElementById('locationSelect');
            const courtSelect = document.getElementById('courtSelect');
            const allCourtOptions = Array.from(courtSelect.options);
            const bookForm = document.getElementById('bookCourtForm');
            const termsModal = document.getElementById('pbjTermsModal');
            const agreeCheckbox = document.getElementById('agreeTermsCheckbox');
            const submitBookingBtn = document.getElementById('submitBookingBtn');
            const closeTermsBtn = document.getElementById('closeTermsBtn');

            let priceCalculationController = null;

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
                const reservationDate = document.getElementById('reservationDate')?.value;
                const startTime   = document.getElementById('startTime')?.value;
                const durationSelect = document.getElementById('durationHours');
                if (!courtSelect || !reservationDate || !startTime || !durationSelect) return;

                const duration = parseInt(durationSelect.value, 10);
                const [sh, sm] = startTime.split(':').map(Number);
                const eh = (sh + duration) % 24;
                const em = sm;
                const endTimeStr = `${String(eh).padStart(2, '0')}:${String(em).padStart(2, '0')}`;
                document.getElementById('endTime').value = endTimeStr;

                // Calculate equipment locally
                let eqTotal = 0;
                let eqParts = [];
                document.querySelectorAll('.equipment-qty').forEach(function (inp) {
                    const qty = parseInt(inp.value) || 0;
                    const price = parseFloat(inp.dataset.price) || 0;
                    if (qty > 0) {
                        eqTotal += qty * price;
                        const itemName = inp.closest('.mb-3')?.querySelector('label')?.textContent || 'Item';
                        eqParts.push(`${itemName} (PHP ${(qty * price).toFixed(2)})`);
                    }
                });
                eqTotal = Math.round(eqTotal * 100) / 100;

                const courtId = courtSelect.value;
                if (!courtId) return;

                // Cancel any pending fetch request
                if (priceCalculationController) {
                    priceCalculationController.abort();
                }
                priceCalculationController = new AbortController();

                // Fetch pricing breakdown
                fetch('{{ route('bookings.calculate-price') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    signal: priceCalculationController.signal,
                    body: JSON.stringify({
                        court_id: courtId,
                        reservation_date: reservationDate,
                        start_time: startTime,
                        duration: duration
                    })
                })
                .then(res => {
                    if (!res.ok) throw new Error('Failed to calculate price');
                    return res.json();
                })
                .then(data => {
                    const courtTotal = parseFloat(data.total) || 0;
                    const grand = courtTotal + eqTotal;

                    document.getElementById('totalAmountDisplay').textContent =
                        'PHP ' + grand.toLocaleString('en-PH', {minimumFractionDigits:2, maximumFractionDigits:2});

                    let breakdownHtml = '<strong>Court Pricing:</strong> ';
                    const itemsText = data.items.map(item => `${item.label} (PHP ${item.rate.toFixed(2)})`).join(', ');
                    breakdownHtml += itemsText + ` = <strong>PHP ${courtTotal.toFixed(2)}</strong>`;

                    if (eqParts.length > 0) {
                        breakdownHtml += `<br><strong>Equipment:</strong> ` + eqParts.join(' + ') + ` = <strong>PHP ${eqTotal.toFixed(2)}</strong>`;
                    }
                    
                    document.getElementById('totalBreakdown').innerHTML = breakdownHtml;
                })
                .catch(err => {
                    if (err.name === 'AbortError') return;
                    console.error(err);
                    document.getElementById('totalBreakdown').textContent = 'Error calculating price. Please check date/time.';
                });
            }

            // Live Availability Grid Fetching and Synchronizing Logic
            const gridLocationSelect = document.getElementById('grid-location-select');
            const gridDatePicker = document.getElementById('grid-date-picker');
            const gridTimelineContainer = document.getElementById('grid-availability-timeline');

            function fetchGridAvailability() {
                if (!gridLocationSelect || !gridDatePicker || !gridTimelineContainer) return;
                const locationId = gridLocationSelect.value;
                const date = gridDatePicker.value;

                if (!locationId || !date) return;

                gridTimelineContainer.innerHTML = `
                    <div class="text-center py-4 text-secondary">
                        <i class="fas fa-spinner fa-spin fa-2x mb-2"></i>
                        <p class="text-xxs mb-0">Loading availability...</p>
                    </div>
                `;

                fetch(`/court-availability?location_id=${locationId}&date=${date}`)
                    .then(response => {
                        if (!response.ok) throw new Error('Network response was not ok');
                        return response.json();
                    })
                    .then(data => {
                        if (!data.courts || data.courts.length === 0) {
                            gridTimelineContainer.innerHTML = `
                                <div class="text-center py-4 text-secondary">
                                    <i class="fas fa-exclamation-circle fa-2x mb-2 text-danger"></i>
                                    <p class="text-xxs mb-0">No active courts found at this location.</p>
                                </div>
                            `;
                            return;
                        }

                        let html = '';
                        data.courts.forEach(court => {
                            html += `
                                <div class="mb-3 pb-3 border-bottom text-start">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 class="text-xs font-weight-bold text-dark mb-0">
                                            <i class="fas fa-table-tennis text-info me-1"></i>
                                            ${court.court_name} (${court.court_number})
                                        </h6>
                                    </div>
                                    <div class="slots-timeline">
                            `;

                            court.slots.forEach(slot => {
                                if (slot.reason === 'Closed') {
                                    return; 
                                }

                                if (slot.available) {
                                    const slotStartStr = slot.start;
                                    html += `
                                        <div class="slot-badge slot-hover" 
                                             style="cursor: pointer;"
                                             onclick="pbjInitCustomerBook(${court.court_id}, '${slotStartStr}', '${date}', ${locationId})"
                                             title="Available">
                                            <span class="slot-label font-weight-bold">${slot.label.split(' - ')[0]}</span>
                                            <span class="btn-slot-action btn-book">
                                                <i class="fas fa-plus"></i> Book
                                            </span>
                                        </div>
                                    `;
                                } else if (slot.reason === 'Ongoing Payment') {
                                    html += `
                                        <div class="slot-badge slot-ongoing-payment" title="Ongoing payment - please wait">
                                            <span class="slot-label">${slot.label.split(' - ')[0]}</span>
                                            <span class="btn-slot-action btn-ongoing">
                                                <i class="fas fa-clock fa-spin me-1"></i>
                                                <span class="countdown-timer" data-expires="${slot.expires_at}">Ongoing</span>
                                            </span>
                                        </div>
                                    `;
                                } else {
                                    html += `
                                        <div class="slot-badge slot-unavailable" title="${slot.reason}">
                                            <span class="slot-label">${slot.label.split(' - ')[0]}</span>
                                            <span class="btn-slot-action btn-blocked">
                                                <i class="fas fa-ban"></i> ${slot.reason}
                                            </span>
                                        </div>
                                    `;
                                }
                            });

                            html += `
                                    </div>
                                </div>
                            `;
                        });

                        gridTimelineContainer.innerHTML = html;
                    })
                    .catch(err => {
                        console.error(err);
                        gridTimelineContainer.innerHTML = `
                            <div class="text-center py-4 text-secondary">
                                <i class="fas fa-exclamation-triangle fa-2x mb-2 text-danger"></i>
                                <p class="text-xxs mb-0">Failed to load availability. Please try again.</p>
                            </div>
                        `;
                    });
            }

            // Sync grid location change to form & refetch availability
            if (gridLocationSelect) {
                gridLocationSelect.addEventListener('change', function () {
                    if (locationSelect && locationSelect.value !== gridLocationSelect.value) {
                        locationSelect.value = gridLocationSelect.value;
                        filterCourts();
                    }
                    fetchGridAvailability();
                });
            }

            // Sync grid date picker to form & refetch availability
            if (gridDatePicker) {
                gridDatePicker.addEventListener('change', function () {
                    const reservationDateInput = document.getElementById('reservationDate');
                    if (reservationDateInput && reservationDateInput.value !== gridDatePicker.value) {
                        reservationDateInput.value = gridDatePicker.value;
                        calcTotal();
                    }
                    fetchGridAvailability();
                });
            }

            // Click slot helper
            window.pbjInitCustomerBook = function (courtId, startTime, date, locationId) {
                if (locationSelect && locationSelect.value !== String(locationId)) {
                    locationSelect.value = locationId;
                    if (gridLocationSelect) {
                        gridLocationSelect.value = locationId;
                    }
                    filterCourts();
                }

                const reservationDateInput = document.getElementById('reservationDate');
                if (reservationDateInput && reservationDateInput.value !== date) {
                    reservationDateInput.value = date;
                    if (gridDatePicker) {
                        gridDatePicker.value = date;
                    }
                }

                if (courtSelect) {
                    courtSelect.value = courtId;
                }

                const startTimeInput = document.getElementById('startTime');
                if (startTimeInput) {
                    startTimeInput.value = startTime;
                }

                // Reset duration to 1 hour upon selection or keep it
                const durationSelect = document.getElementById('durationHours');
                if (durationSelect) {
                    durationSelect.value = "1";
                }

                calcTotal();

                // Smooth scroll to form
                const formCard = document.getElementById('bookCourtForm');
                if (formCard) {
                    formCard.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            };

            // Event Listeners for Pricing
            ['courtSelect','startTime','durationHours'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.addEventListener('change', calcTotal);
            });

            const reservationDateInput = document.getElementById('reservationDate');
            if (reservationDateInput) {
                reservationDateInput.addEventListener('change', function () {
                    if (gridDatePicker) {
                        gridDatePicker.value = reservationDateInput.value;
                    }
                    calcTotal();
                    fetchGridAvailability();
                });
            }

            if (locationSelect) {
                locationSelect.addEventListener('change', function () {
                    if (gridLocationSelect) {
                        gridLocationSelect.value = locationSelect.value;
                    }
                    filterCourts();
                    fetchGridAvailability();
                });
            }

            document.querySelectorAll('.equipment-qty').forEach(el => el.addEventListener('input', calcTotal));
            
            // Set initial duration based on start/end query parameters diff if present
            const qStart = '{{ request()->query('start_time') }}';
            const qEnd = '{{ request()->query('end_time') }}';
            if (qStart && qEnd) {
                const [sh] = qStart.split(':').map(Number);
                const [eh] = qEnd.split(':').map(Number);
                const diff = eh - sh;
                if (diff >= 1 && diff <= 4) {
                    document.getElementById('durationHours').value = diff;
                }
            }

            // Global countdown timer for ongoing payments
            setInterval(() => {
                const timers = document.querySelectorAll('.countdown-timer[data-expires]');
                timers.forEach(timer => {
                    const expiresAt = parseInt(timer.dataset.expires, 10);
                    const now = Date.now();
                    const diff = expiresAt - now;

                    if (diff <= 0) {
                        timer.textContent = 'Expiring...';
                        if (!timer.dataset.expiredTriggered) {
                            timer.dataset.expiredTriggered = 'true';
                            setTimeout(fetchGridAvailability, 1500);
                        }
                    } else {
                        const mins = Math.floor(diff / 60000);
                        const secs = Math.floor((diff % 60000) / 1000);
                        timer.textContent = `${mins}:${secs.toString().padStart(2, '0')}`;
                    }
                });
            }, 1000);

            // Initial calls
            filterCourts();
            fetchGridAvailability();

            // Terms and Conditions Interceptor Logic
            if (bookForm && termsModal) {
                bookForm.addEventListener('submit', function (e) {
                    if (bookForm.dataset.termsAccepted === 'true') {
                        return; 
                    }
                    
                    e.preventDefault(); 
                    agreeCheckbox.checked = false;
                    submitBookingBtn.disabled = true;
                    termsModal.style.display = 'flex';
                });

                agreeCheckbox.addEventListener('change', function () {
                    submitBookingBtn.disabled = !agreeCheckbox.checked;
                });

                closeTermsBtn.addEventListener('click', function () {
                    termsModal.style.display = 'none';
                });

                submitBookingBtn.addEventListener('click', function () {
                    bookForm.dataset.termsAccepted = 'true';
                    termsModal.style.display = 'none';
                    bookForm.submit();
                });
            }
        })();
        </script>
    @endif

    {{-- =========================================================
         LOCATIONS (admin) - modal-driven CRUD
         ========================================================= --}}
    {{-- =========================================================
         WALK-INS (staff) - Counter booking & payments (H1-H7)
         ========================================================= --}}
    @if ($module === 'walk-ins')
        <style>
            .slots-timeline {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(110px, 1fr));
                gap: 0.5rem;
                width: 100%;
                margin-top: 0.5rem;
            }
            @media (max-width: 500px) {
                .slots-timeline {
                    grid-template-columns: repeat(2, 1fr) !important;
                }
            }
            .slot-badge {
                border-radius: 8px;
                padding: 0.5rem;
                text-align: center;
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 0.35rem;
                width: 100%;
                transition: all 0.2s ease-in-out;
            }
            /* Available Slot styling */
            .slot-badge.slot-hover {
                background-color: rgba(45, 206, 137, 0.08);
                border: 1px solid rgba(45, 206, 137, 0.25);
            }
            .slot-badge.slot-hover:hover {
                background-color: rgba(45, 206, 137, 0.18) !important;
                border-color: #2dce89 !important;
                transform: translateY(-2px);
                box-shadow: 0 4px 8px rgba(45, 206, 137, 0.15);
            }
            .slot-badge.slot-hover .slot-label {
                color: #2dce89;
                font-weight: 700;
                font-size: 0.75rem;
            }
            .slot-badge .btn-slot-action {
                padding: 0.25rem 0.5rem;
                font-size: 0.65rem;
                border-radius: 4px;
                font-weight: 700;
                width: 100%;
                text-transform: uppercase;
                letter-spacing: 0.05em;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 0.2rem;
                margin: 0;
                border: none;
            }
            .slot-badge .btn-slot-action.btn-book {
                background-color: #2dce89;
                color: #fff;
            }
            .slot-badge.slot-hover:hover .btn-slot-action.btn-book {
                background-color: #2dce89;
                box-shadow: 0 2px 4px rgba(45, 206, 137, 0.2);
            }
            
            /* Unavailable Slot styling - RED */
            .slot-badge.slot-unavailable {
                background-color: rgba(245, 54, 92, 0.08) !important;
                border: 1px solid rgba(245, 54, 92, 0.25) !important;
                cursor: not-allowed;
            }
            .slot-badge.slot-unavailable .slot-label {
                color: #f5365c;
                font-weight: 700;
                font-size: 0.75rem;
                text-decoration: line-through;
            }
            .slot-badge .btn-slot-action.btn-blocked {
                background-color: #f5365c;
                color: #fff;
                font-size: 0.6rem;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }
        </style>
        <div class="row mb-4">
            <!-- Left Column: Search/List & Actions -->
            <div class="col-lg-7 col-md-12 mb-4">
                <div class="card shadow-lg h-100">
                    <div class="card-header pb-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div>
                            <h6 class="mb-0">Recent Walk-In Bookings</h6>
                            <p class="text-xs text-secondary mb-0">List of all counter reservations created by staff.</p>
                        </div>
                    </div>
                    <div class="card-body px-0 pb-2">
                        <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder">Code / Customer</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder">Details</th>
                                        <th class="text-end text-uppercase text-secondary text-xxs font-weight-bolder">Total</th>
                                        <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder">Status</th>
                                        <th class="text-end text-uppercase text-secondary text-xxs font-weight-bolder pe-3">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse (($page['rawReservations'] ?? []) as $res)
                                        <tr>
                                            <td>
                                                <div class="d-flex px-2 py-1">
                                                    <div class="avatar avatar-sm bg-gradient-dark me-2 d-flex align-items-center justify-content-center">
                                                        <i class="fas fa-walking text-white text-xs"></i>
                                                    </div>
                                                    <div>
                                                        <h6 class="mb-0 text-xs font-weight-bold">{{ $res->reservation_code }}</h6>
                                                        <p class="text-xxs text-secondary mb-0">{{ $res->customer_name }} ({{ $res->mobile_number }})</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <p class="text-xs font-weight-bold mb-0">{{ $res->location_name }} — {{ $res->court_name ?: 'Court '.$res->court_number }}</p>
                                                <p class="text-xxs text-secondary mb-0">{{ $res->reservation_date }} ({{ date('g:i A', strtotime($res->start_time)) }} - {{ date('g:i A', strtotime($res->end_time)) }})</p>
                                            </td>
                                            <td class="text-end text-xs font-weight-bold">PHP {{ number_format($res->grand_total, 2) }}</td>
                                            <td class="text-center">
                                                <span class="badge badge-sm bg-gradient-{{ $res->status === 'confirmed' ? 'success' : ($res->status === 'cancelled' ? 'danger' : 'warning') }} py-1 px-2" style="font-size: 9px;">
                                                    {{ $res->status }}
                                                </span>
                                                <br>
                                                <span class="text-xxs text-secondary">{{ $res->payment_status }}</span>
                                            </td>
                                            <td class="text-end pe-3">
                                                @if ($res->payment_status === 'unpaid' && $res->status !== 'cancelled')
                                                    <button type="button" class="btn btn-sm bg-gradient-success mb-1 py-1 px-2 text-xxs" style="font-size:10px;"
                                                        onclick="pbjOpenMarkPaidModal({{ json_encode($res) }})">
                                                        <i class="fas fa-check-circle me-1"></i>Pay
                                                    </button>
                                                @endif
                                                <button type="button" class="btn btn-sm bg-gradient-info mb-1 py-1 px-2 text-xxs" style="font-size:10px;"
                                                    onclick="pbjViewWalkInReceipt({{ json_encode($res) }})">
                                                    <i class="fas fa-file-invoice me-1"></i>Receipt
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-sm text-secondary py-5">
                                                <i class="fas fa-walking fa-2x mb-2 opacity-3"></i>
                                                <p class="mb-0">No walk-in bookings created yet.</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Date-Click Calendar / Slots timeline -->
            <div class="col-lg-5 col-md-12">
                <div class="card shadow-lg h-100">
                    <div class="card-header pb-0">
                        <h6>Live Availability Grid</h6>
                        <p class="text-xs text-secondary mb-0">Select a branch and date to see live court slots. Click an open slot to book.</p>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-6">
                                <label class="form-label text-xxs text-uppercase font-weight-bolder text-secondary">Branch</label>
                                <select id="walkin-location-select" class="form-control form-control-sm">
                                    @foreach (($page['locations'] ?? []) as $loc)
                                        <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label text-xxs text-uppercase font-weight-bolder text-secondary">Date</label>
                                <input type="date" id="walkin-date-picker" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" min="{{ date('Y-m-d') }}">
                            </div>
                        </div>

                        <!-- Slots List group -->
                        <div id="walkin-availability-timeline" class="border rounded p-2" style="max-height: 400px; overflow-y: auto; background: #fafafa;">
                            <!-- Loaded dynamically via fetch -->
                            <div class="text-center py-4 text-secondary">
                                <i class="fas fa-spinner fa-spin me-2"></i>Loading slots...
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @push('modals')
            <!-- Quick pre-filled booking modal -->
            <div class="modal fade" id="quickBookModal" tabindex="-1" aria-labelledby="quickBookModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="quickBookModalLabel">Quick Walk-In Booking</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body text-start">
                            <form id="quickBookForm" method="POST" action="{{ route('walk-ins.store') }}">
                                @csrf
                                <input type="hidden" name="court_id" id="qb_court_id">
                                <input type="hidden" name="reservation_date" id="qb_reservation_date">
                                
                                <div class="row">
                                    <div class="col-12 mb-3">
                                        <p class="text-sm font-weight-bold mb-1">Reservation Details</p>
                                        <span class="badge bg-gradient-info text-xxs px-2 py-1" id="qb_details_label">Court A — 8:00 AM</span>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <label class="form-label text-xs">Start Time</label>
                                        <input type="time" name="start_time" id="qb_start_time" class="form-control" readonly required>
                                    </div>
                                    <div class="col-6 mb-3">
                                        <label class="form-label text-xs">Duration</label>
                                        <select name="duration" id="qb_duration" class="form-control" onchange="qbUpdateEndTime()">
                                            <option value="1">1 Hour</option>
                                            <option value="2">2 Hours</option>
                                            <option value="3">3 Hours</option>
                                            <option value="4">4 Hours</option>
                                        </select>
                                        <input type="hidden" name="end_time" id="qb_end_time">
                                    </div>
                                    
                                    <div class="col-6 mb-3">
                                        <label class="form-label text-xs">Customer First Name</label>
                                        <input type="text" name="customer_first_name" class="form-control" required placeholder="Juan">
                                    </div>
                                    <div class="col-6 mb-3">
                                        <label class="form-label text-xs">Customer Last Name</label>
                                        <input type="text" name="customer_last_name" class="form-control" required placeholder="Dela Cruz">
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label class="form-label text-xs">Customer Mobile Number</label>
                                        <input type="text" name="customer_mobile" class="form-control" required placeholder="09123456789">
                                    </div>
                                    <div class="col-12 mb-3 text-start">
                                        <div class="form-check form-switch ps-0 mb-0">
                                            <input class="form-check-input ms-auto" type="checkbox" name="create_account" id="qb_create_account" value="1">
                                            <label class="form-check-label text-xs font-weight-bold ms-3" for="qb_create_account">Create Guest Customer Account</label>
                                        </div>
                                    </div>
                                    
                                    <!-- Equipment Rentals -->
                                    <div class="col-12 mb-3 text-start">
                                        <label class="form-label text-xs font-weight-bold"><i class="fas fa-boxes text-info me-1"></i>Rent Paddles / Equipment (Optional)</label>
                                        <div class="row">
                                            @forelse (($page['bookingEquipment'] ?? []) as $item)
                                                <div class="col-6 mb-2">
                                                    <label class="form-label text-xxs mb-1 text-secondary">{{ $item['name'] }} (PHP {{ number_format($item['price'], 2) }}/unit)</label>
                                                    <div class="input-group input-group-sm">
                                                        <span class="input-group-text">Qty</span>
                                                        <input type="number" 
                                                               name="equipment[{{ $item['id'] }}]" 
                                                               class="form-control qb-equipment-qty" 
                                                               data-price="{{ $item['price'] }}"
                                                               min="0" 
                                                               max="{{ min($item['max'], $item['available']) }}" 
                                                               value="0"
                                                               oninput="qbUpdateGrandTotal()">
                                                    </div>
                                                    <span class="text-xxs text-secondary">{{ $item['available'] }} available</span>
                                                </div>
                                            @empty
                                                <div class="col-12">
                                                    <p class="text-xxs text-secondary mb-0">No rentable equipment available.</p>
                                                </div>
                                            @endforelse
                                        </div>
                                    </div>
                                    
                                    <div class="col-12"><hr class="horizontal dark my-2"></div>
                                    
                                    <div class="col-12 mb-3 text-start">
                                        <label class="form-label text-xs">Payment Method</label>
                                        <select name="payment_method" id="qb_payment_method" class="form-control" onchange="qbTogglePaymentFields()" required>
                                            <option value="cash" selected>Cash Payment</option>
                                        </select>
                                    </div>
                                    
                                    <!-- Cash Fields -->
                                    <div class="col-12 mb-3 qb-payment-fields" id="qb_cash_fields" style="display:none;">
                                        <div class="row">
                                            <div class="col-6">
                                                <label class="form-label text-xs">Total Amount</label>
                                                <h6 class="font-weight-bold text-dark text-sm mt-1" id="qb_cash_total_label">PHP 0.00</h6>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label text-xs">Cash Received (PHP)</label>
                                                <input type="number" name="cash_received" id="qb_cash_received" class="form-control" min="0" step="0.01" oninput="qbCalcChange()">
                                            </div>
                                            <div class="col-12 mt-2">
                                                <p class="text-xs text-secondary mb-0" id="qb_change_label">Change: PHP 0.00</p>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <!-- GCash Fields -->
                                    <div class="col-12 mb-3 qb-payment-fields" id="qb_gcash_fields" style="display:none;">
                                        <div class="row">
                                            <div class="col-6">
                                                <label class="form-label text-xs">Total Amount</label>
                                                <h6 class="font-weight-bold text-dark text-sm mt-1" id="qb_gcash_total_label">PHP 0.00</h6>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label text-xs">GCash Ref Number</label>
                                                <input type="text" name="gcash_reference_number" class="form-control" placeholder="100XXXXXXXX">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-12 mb-3">
                                        <label class="form-label text-xs">Special Requests (Optional)</label>
                                        <textarea name="special_requests" class="form-control" rows="2" placeholder="Optional comments..."></textarea>
                                    </div>
                                </div>
                                <div class="text-end mt-3">
                                    <button type="button" class="btn btn-outline-secondary mb-0" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn bg-gradient-primary mb-0">Confirm Booking</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mark as Paid Modal -->
            <div class="modal fade" id="markPaidModal" tabindex="-1" aria-labelledby="markPaidModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="markPaidModalLabel">Record Counter Payment</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body text-start">
                            <form id="markPaidForm" method="POST">
                                @csrf
                                <div class="row">
                                    <div class="col-12 mb-3">
                                        <p class="text-xs text-secondary mb-0">Reservation Code</p>
                                        <h6 class="text-sm font-weight-bold text-dark" id="mp_code_label">PBJ-W-...</h6>
                                        <p class="text-xs text-secondary mb-0" id="mp_details_label">Customer Details</p>
                                    </div>
                                    <div class="col-12 mb-3">
                                        <label class="form-label text-xs">Payment Method</label>
                                        <select name="payment_method" id="mp_payment_method" class="form-control" required onchange="mpTogglePaymentFields()">
                                            <option value="cash">Cash Payment</option>
                                        </select>
                                    </div>
                                    <div class="col-12 mb-3" id="mp_cash_fields">
                                        <div class="row">
                                            <div class="col-6">
                                                <label class="form-label text-xs">Total Amount</label>
                                                <h6 class="font-weight-bold text-dark text-sm mt-1" id="mp_cash_total_label">PHP 0.00</h6>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label text-xs">Cash Received (PHP)</label>
                                                <input type="number" name="cash_received" id="mp_cash_received" class="form-control" min="0" step="0.01" oninput="mpCalcChange()">
                                            </div>
                                            <div class="col-12 mt-2">
                                                <p class="text-xs text-secondary mb-0" id="mp_change_label">Change: PHP 0.00</p>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12 mb-3" id="mp_gcash_fields" style="display:none;">
                                        <div class="row">
                                            <div class="col-6">
                                                <label class="form-label text-xs">Total Amount</label>
                                                <h6 class="font-weight-bold text-dark text-sm mt-1" id="mp_gcash_total_label">PHP 0.00</h6>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label text-xs">GCash Ref Number</label>
                                                <input type="text" name="gcash_reference_number" class="form-control" placeholder="100XXXXXXXX">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-end mt-3">
                                    <button type="button" class="btn btn-outline-secondary mb-0" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn bg-gradient-success mb-0">Record Payment</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Receipt Modal (Screenshot / Print Friendly) -->
            <div class="modal fade" id="walkinReceiptModal" tabindex="-1" aria-labelledby="walkinReceiptModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content shadow-2xl">
                        <div class="modal-body p-4 text-start" id="printableReceiptArea">
                            <style>
                                @media print {
                                    body * { visibility: hidden; }
                                    #printableReceiptArea, #printableReceiptArea * { visibility: visible; }
                                    #printableReceiptArea { position: absolute; left: 0; top: 0; width: 100%; }
                                    .no-print { display: none !important; }
                                }
                                .receipt-border {
                                    border: 2px dashed #e9ecef;
                                    border-radius: 8px;
                                    padding: 1.5rem;
                                    background: #fff;
                                }
                            </style>
                            <div class="receipt-border text-center">
                                <img src="{{ asset('images/branding.png') }}" class="mb-2" style="width: 50px; height: 50px; border-radius: 50%;" alt="Logo">
                                <h6 class="font-weight-bold text-dark mb-0">Pickle Ballan ni Juan</h6>
                                <p class="text-xxs text-secondary mb-3">Court Booking Receipt</p>
                                <div class="border-top border-bottom py-2 my-2 text-start">
                                    <div class="d-flex justify-content-between"><span class="text-xxs text-secondary">Receipt Code:</span><span class="text-xs font-weight-bold text-dark" id="rc_code">PBJ-W-...</span></div>
                                    <div class="d-flex justify-content-between"><span class="text-xxs text-secondary">Date:</span><span class="text-xs text-dark" id="rc_date">2026-06-20</span></div>
                                    <div class="d-flex justify-content-between"><span class="text-xxs text-secondary">Time window:</span><span class="text-xs text-dark" id="rc_time">8:00 AM - 9:00 AM</span></div>
                                </div>
                                <div class="text-start mb-3">
                                    <div class="d-flex justify-content-between"><span class="text-xxs text-secondary">Customer:</span><span class="text-xs font-weight-bold text-dark" id="rc_customer">Juan Dela Cruz</span></div>
                                    <div class="d-flex justify-content-between"><span class="text-xxs text-secondary">Mobile:</span><span class="text-xs text-dark" id="rc_mobile">09123456789</span></div>
                                    <div class="d-flex justify-content-between"><span class="text-xxs text-secondary">Court name:</span><span class="text-xs text-dark" id="rc_court">Court A</span></div>
                                </div>
                                <div class="bg-light p-2 rounded mb-3 text-start">
                                    <div class="d-flex justify-content-between mb-1"><span class="text-xxs font-weight-bold">Court Fee Subtotal:</span><span class="text-xs text-dark font-weight-bold" id="rc_subtotal">PHP 600.00</span></div>
                                    <div class="d-flex justify-content-between mb-1"><span class="text-xxs font-weight-bold">Equipment Rent:</span><span class="text-xs text-dark" id="rc_equipment">PHP 0.00</span></div>
                                    <div class="d-flex justify-content-between border-top pt-1"><span class="text-xs font-weight-bold text-primary">Grand Total:</span><span class="text-xs font-weight-bolder text-primary" id="rc_total">PHP 600.00</span></div>
                                </div>
                                <div class="text-start text-xxs text-secondary mb-3">
                                    <div class="d-flex justify-content-between"><span>Payment Method:</span><span class="font-weight-bold text-dark text-capitalize" id="rc_method">Cash</span></div>
                                    <div class="d-flex justify-content-between"><span>Payment Status:</span><span class="font-weight-bold text-success text-capitalize" id="rc_status">Paid</span></div>
                                </div>
                                <p class="text-xxs text-center text-muted mb-0">Thank you for playing with us!</p>
                            </div>
                            <div class="d-flex justify-content-end gap-2 mt-4 no-print">
                                <button type="button" class="btn btn-sm btn-outline-secondary mb-0" data-bs-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-sm bg-gradient-info mb-0" onclick="window.print()"><i class="fas fa-print me-1"></i>Print / Save PDF</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endpush

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const locationSelect = document.getElementById('walkin-location-select');
                const datePicker = document.getElementById('walkin-date-picker');
                const timelineContainer = document.getElementById('walkin-availability-timeline');

                if (!locationSelect || !datePicker || !timelineContainer) return;

                let qbModal = null;
                let mpModal = null;
                let rcModal = null;

                function fetchAvailability() {
                    const locationId = locationSelect.value;
                    const date = datePicker.value;

                    if (!locationId || !date) return;

                    timelineContainer.innerHTML = `
                        <div class="text-center py-4 text-secondary">
                            <i class="fas fa-spinner fa-spin fa-2x mb-2"></i>
                            <p class="text-xxs mb-0">Loading availability...</p>
                        </div>
                    `;

                    fetch(`/court-availability?location_id=${locationId}&date=${date}`)
                        .then(response => {
                            if (!response.ok) throw new Error('Network response was not ok');
                            return response.json();
                        })
                        .then(data => {
                            if (!data.courts || data.courts.length === 0) {
                                timelineContainer.innerHTML = `
                                    <div class="text-center py-4 text-secondary">
                                        <i class="fas fa-exclamation-circle fa-2x mb-2 text-danger"></i>
                                        <p class="text-xxs mb-0">No active courts found at this location.</p>
                                    </div>
                                `;
                                return;
                            }

                            let html = '';
                            data.courts.forEach(court => {
                                html += `
                                    <div class="mb-3 pb-3 border-bottom text-start">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="text-xs font-weight-bold text-dark mb-0">
                                                <i class="fas fa-table-tennis text-info me-1"></i>
                                                ${court.court_name} (${court.court_number})
                                            </h6>
                                        </div>
                                        <div class="slots-timeline">
                                `;

                                court.slots.forEach(slot => {
                                    if (slot.reason === 'Closed') {
                                        return; 
                                    }

                                    if (slot.available) {
                                        const slotStartStr = slot.start;
                                        html += `
                                            <div class="slot-badge slot-hover" 
                                                 style="cursor: pointer;"
                                                 onclick='pbjInitQuickBook(${court.court_id}, "${court.court_name}", "${slotStartStr}", "${date}")'
                                                 title="Available">
                                                <span class="slot-label font-weight-bold">${slot.label.split(' - ')[0]}</span>
                                                <span class="btn-slot-action btn-book">
                                                    <i class="fas fa-plus"></i> Book
                                                </span>
                                            </div>
                                        `;
                                    } else {
                                        html += `
                                            <div class="slot-badge slot-unavailable" title="${slot.reason}">
                                                <span class="slot-label">${slot.label.split(' - ')[0]}</span>
                                                <span class="btn-slot-action btn-blocked">
                                                    <i class="fas fa-ban"></i> ${slot.reason}
                                                </span>
                                            </div>
                                        `;
                                    }
                                });

                                html += `
                                        </div>
                                    </div>
                                `;
                            });

                            timelineContainer.innerHTML = html;
                        })
                        .catch(err => {
                            timelineContainer.innerHTML = `
                                <div class="text-center py-4 text-secondary">
                                    <i class="fas fa-exclamation-triangle fa-2x mb-2 text-danger"></i>
                                    <p class="text-xxs mb-0">Failed to load availability. Please try again.</p>
                                </div>
                            `;
                        });
                }

                locationSelect.addEventListener('change', fetchAvailability);
                datePicker.addEventListener('change', fetchAvailability);

                fetchAvailability();

                // Quick booking helper functions
                let qbCourtSubtotal = 0.0;

                window.pbjInitQuickBook = function(courtId, courtName, startTime, date) {
                    document.getElementById('qb_court_id').value = courtId;
                    document.getElementById('qb_reservation_date').value = date;
                    document.getElementById('qb_start_time').value = startTime;
                    document.getElementById('qb_duration').value = '1';
                    
                    document.getElementById('qb_details_label').textContent = `${courtName} on ${date} @ ${startTime}`;
                    
                    // Reset equipment quantities
                    document.querySelectorAll('.qb-equipment-qty').forEach(el => el.value = 0);
                    qbCourtSubtotal = 0.0;
                    
                    qbUpdateEndTime();
                    qbTogglePaymentFields();
                    
                    if (!qbModal) {
                        qbModal = new bootstrap.Modal(document.getElementById('quickBookModal'));
                    }
                    qbModal.show();
                };

                window.qbUpdateEndTime = function() {
                    const startVal = document.getElementById('qb_start_time').value;
                    const duration = parseInt(document.getElementById('qb_duration').value, 10);
                    if (!startVal) return;
                    
                    const [h, m] = startVal.split(':').map(Number);
                    const endH = (h + duration) % 24;
                    const endVal = `${String(endH).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
                    document.getElementById('qb_end_time').value = endVal;
                    
                    // Fetch dynamic price sum via AJAX for precise rates
                    const courtId = document.getElementById('qb_court_id').value;
                    const date = document.getElementById('qb_reservation_date').value;
                    
                    fetch('{{ route('bookings.calculate-price') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({
                            court_id: courtId,
                            reservation_date: date,
                            start_time: startVal,
                            duration: duration
                        })
                    })
                    .then(res => res.json())
                    .then(data => {
                        qbCourtSubtotal = parseFloat(data.total) || 0;
                        qbUpdateGrandTotal();
                    });
                };

                window.qbUpdateGrandTotal = function() {
                    let eqTotal = 0.0;
                    document.querySelectorAll('.qb-equipment-qty').forEach(function(inp) {
                        const qty = parseInt(inp.value, 10) || 0;
                        const price = parseFloat(inp.getAttribute('data-price')) || 0;
                        eqTotal += qty * price;
                    });
                    const grandTotal = qbCourtSubtotal + eqTotal;
                    
                    document.getElementById('qb_cash_total_label').textContent = 'PHP ' + grandTotal.toFixed(2);
                    document.getElementById('qb_gcash_total_label').textContent = 'PHP ' + grandTotal.toFixed(2);
                    document.getElementById('qb_cash_received').value = grandTotal.toFixed(2);
                    qbCalcChange();
                };

                window.qbTogglePaymentFields = function() {
                    const method = document.getElementById('qb_payment_method').value;
                    document.querySelectorAll('.qb-payment-fields').forEach(el => el.style.display = 'none');
                    if (method === 'cash') {
                        document.getElementById('qb_cash_fields').style.display = 'block';
                    } else if (method === 'gcash') {
                        document.getElementById('qb_gcash_fields').style.display = 'block';
                    }
                };

                window.qbCalcChange = function() {
                    const totalStr = document.getElementById('qb_cash_total_label').textContent.replace('PHP ', '');
                    const total = parseFloat(totalStr) || 0;
                    const received = parseFloat(document.getElementById('qb_cash_received').value) || 0;
                    const change = Math.max(0, received - total);
                    document.getElementById('qb_change_label').textContent = 'Change: PHP ' + change.toFixed(2);
                };

                // Mark Paid modal functions
                window.pbjOpenMarkPaidModal = function(reservation) {
                    if (!reservation) return;
                    
                    document.getElementById('mp_code_label').textContent = reservation.reservation_code;
                    document.getElementById('mp_details_label').innerHTML = `
                        <strong>Customer:</strong> ${reservation.customer_name}<br>
                        <strong>Court:</strong> ${reservation.location_name} — ${reservation.court_name || 'Court '+reservation.court_number}<br>
                        <strong>Schedule:</strong> ${reservation.reservation_date} (${reservation.start_time} - ${reservation.end_time})
                    `;
                    
                    document.getElementById('mp_cash_total_label').textContent = 'PHP ' + parseFloat(reservation.grand_total).toFixed(2);
                    document.getElementById('mp_gcash_total_label').textContent = 'PHP ' + parseFloat(reservation.grand_total).toFixed(2);
                    document.getElementById('mp_cash_received').value = parseFloat(reservation.grand_total).toFixed(2);
                    
                    document.getElementById('markPaidForm').action = `/walk-ins/${reservation.id}/mark-paid`;
                    
                    mpCalcChange();
                    mpTogglePaymentFields();
                    
                    if (!mpModal) {
                        mpModal = new bootstrap.Modal(document.getElementById('markPaidModal'));
                    }
                    mpModal.show();
                };

                window.mpTogglePaymentFields = function() {
                    const method = document.getElementById('mp_payment_method').value;
                    document.getElementById('mp_cash_fields').style.display = method === 'cash' ? 'block' : 'none';
                    document.getElementById('mp_gcash_fields').style.display = method === 'gcash' ? 'block' : 'none';
                };

                window.mpCalcChange = function() {
                    const totalStr = document.getElementById('mp_cash_total_label').textContent.replace('PHP ', '');
                    const total = parseFloat(totalStr) || 0;
                    const received = parseFloat(document.getElementById('mp_cash_received').value) || 0;
                    const change = Math.max(0, received - total);
                    document.getElementById('mp_change_label').textContent = 'Change: PHP ' + change.toFixed(2);
                };

                // Receipt view functions
                window.pbjViewWalkInReceipt = function(reservation) {
                    if (!reservation) return;
                    
                    document.getElementById('rc_code').textContent = reservation.reservation_code;
                    document.getElementById('rc_date').textContent = reservation.reservation_date;
                    document.getElementById('rc_time').textContent = `${pbjFormatTime(reservation.start_time)} - ${pbjFormatTime(reservation.end_time)}`;
                    document.getElementById('rc_customer').textContent = reservation.customer_name;
                    document.getElementById('rc_mobile').textContent = reservation.mobile_number || '—';
                    document.getElementById('rc_court').textContent = `${reservation.court_name || 'Court '+reservation.court_number} (${reservation.location_name})`;
                    document.getElementById('rc_subtotal').textContent = 'PHP ' + parseFloat(reservation.grand_total).toFixed(2);
                    document.getElementById('rc_equipment').textContent = 'PHP 0.00';
                    document.getElementById('rc_total').textContent = 'PHP ' + parseFloat(reservation.grand_total).toFixed(2);
                    
                    document.getElementById('rc_method').textContent = reservation.payment_status === 'paid' ? 'Counter' : 'Unpaid';
                    document.getElementById('rc_status').textContent = reservation.payment_status === 'paid' ? 'Verified Paid' : 'Pending Payment';
                    
                    const statusEl = document.getElementById('rc_status');
                    if (reservation.payment_status === 'paid') {
                        statusEl.className = 'font-weight-bold text-success text-capitalize';
                    } else {
                        statusEl.className = 'font-weight-bold text-warning text-capitalize';
                    }
                    
                    if (!rcModal) {
                        rcModal = new bootstrap.Modal(document.getElementById('walkinReceiptModal'));
                    }
                    rcModal.show();
                };
                
                function pbjFormatTime(timeStr) {
                    if (!timeStr) return '';
                    const [h, m] = timeStr.split(':').map(Number);
                    const ampm = h >= 12 ? 'PM' : 'AM';
                    const hour = h % 12 || 12;
                    return `${hour}:${String(m).padStart(2, '0')} ${ampm}`;
                }
            });
        </script>
    @endif

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
         RATES (admin) - modal-driven CRUD
         ========================================================= --}}
    @if ($module === 'rates' && ($page['canManageOperations'] ?? false))
        @include('modules.partials.rates', [
            'courts' => $page['courts'] ?? [],
            'equipment' => $page['equipment'] ?? [],
            'locationOptions' => $page['locationOptions'] ?? [],
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
                                <h6>GCash Payment Settings</h6>
                                <p class="text-sm mb-0">Manage GCash payments. Switch between automatic XPayLink payments or manual proof uploads.</p>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="{{ route('admin.settings.gcash.update') }}" enctype="multipart/form-data">
                                    @csrf
                                    <div class="row">
                                        <!-- Automated Settings (XPayLink) -->
                                        <div class="col-12 mb-3">
                                            <div class="form-check form-switch ps-0">
                                                <input class="form-check-input ms-auto" type="checkbox" name="xpaylink_enabled" id="xpaylink_enabled" value="true" {{ filter_var($page['xpaylinkEnabled'] ?? false, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
                                                <label class="form-check-label text-dark font-weight-bold ms-3" for="xpaylink_enabled">Enable XPayLink Automatic Payments</label>
                                            </div>
                                            <p class="text-xs text-secondary mb-0">When enabled, customers will be redirected to the secure GCash gateway, and their payment will be verified automatically via webhooks.</p>
                                            <div class="mt-2 d-flex align-items-center flex-wrap gap-2">
                                                <span class="badge bg-light text-dark font-weight-bold text-xxs px-2 py-1">Webhook URL:</span>
                                                <code class="text-xs font-weight-bold text-primary bg-light px-2 py-1 rounded border" style="word-break: break-all;">{{ route('payments.xpaylink.webhook') }}</code>
                                            </div>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label text-xs font-weight-bold">XPayLink Public Key</label>
                                            <input type="text" name="xpaylink_public_key" class="form-control" value="{{ $page['xpaylinkPublicKey'] ?? '' }}" placeholder="pk_live_...">
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label text-xs font-weight-bold">XPayLink Secret Key</label>
                                            <input type="password" name="xpaylink_secret_key" class="form-control" value="{{ $page['xpaylinkSecretKey'] ?? '' }}" placeholder="sk_live_...">
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label text-xs font-weight-bold">GCash Pending Hold Timeout (Minutes)</label>
                                            <input type="number" name="booking_timeout_minutes" class="form-control" value="{{ $page['bookingTimeoutMinutes'] ?? 3 }}" min="1" max="60" required>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label class="form-label text-xs font-weight-bold">Max Active Pending Unpaid Bookings Limit</label>
                                            <input type="number" name="max_pending_bookings_limit" class="form-control" value="{{ $page['maxPendingBookingsLimit'] ?? 1 }}" min="1" max="20" required>
                                        </div>

                                        <div class="col-12 mb-3">
                                            <label class="form-label text-xs font-weight-bold">XPayLink API Session Endpoint</label>
                                            <input type="text" name="xpaylink_endpoint" class="form-control" value="{{ $page['xpaylinkEndpoint'] ?? 'https://synthwave.space/api/create-session.php' }}">
                                        </div>

                                        <!-- Divider -->
                                        <div class="col-12">
                                            <hr class="horizontal dark my-3">
                                        </div>

                                        <!-- Manual Settings (Fallback) -->
                                        <div class="col-12 mb-2">
                                            <h6 class="text-xs text-uppercase text-secondary font-weight-bold">Manual GCash Fallback Settings</h6>
                                            <p class="text-xs text-secondary mb-0">Used as a fallback when XPayLink is disabled or unconfigured.</p>
                                        </div>

                                        <div class="col-md-5 mb-3">
                                            <label class="form-label text-xs font-weight-bold">GCash Number</label>
                                            <input type="text" name="owner_gcash_number" class="form-control" value="{{ $page['ownerGcashNumber'] ?? '09123456789' }}" required>
                                        </div>
                                        <div class="col-md-5 mb-3">
                                            <label class="form-label text-xs font-weight-bold">GCash QR Code Image</label>
                                            <input type="file" name="owner_gcash_qr" class="form-control" accept="image/png,image/jpeg,image/webp">
                                        </div>
                                        <div class="col-md-2 mb-3 text-center d-flex align-items-center justify-content-center">
                                            @if (!empty($page['ownerGcashQr']))
                                                <a href="{{ route('public.gcash-qr') }}?v={{ basename($page['ownerGcashQr']) }}" target="_blank">
                                                    <img src="{{ route('public.gcash-qr') }}?v={{ basename($page['ownerGcashQr']) }}" alt="GCash QR Code" class="img-fluid border-radius-md shadow-sm" style="max-height: 45px;">
                                                </a>
                                            @else
                                                <span class="badge bg-light text-dark text-xxs">No QR set</span>
                                            @endif
                                        </div>

                                        <div class="col-12 text-end mt-2">
                                            <button type="submit" class="btn bg-gradient-info mb-0">Save Payment Settings</button>
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
                    <div class="card-header pb-3 bg-gradient-dark position-relative z-index-1">
                        <div class="row align-items-center">
                            <div class="col-lg-5 col-md-4 mb-3 mb-md-0">
                                <h5 class="text-white font-weight-bolder mb-1">
                                    <i class="fas fa-history me-2 text-warning"></i>Book History Store
                                </h5>
                                <p class="text-white text-xs opacity-8 mb-0">
                                    All your approved court bookings, equipment rentals, and receipts are stored here.
                                </p>
                            </div>
                            <div class="col-lg-7 col-md-8 d-flex flex-wrap align-items-center justify-content-md-end gap-2">
                                <form method="GET" action="{{ route('modules.show', 'receipts') }}" id="filterForm" class="d-flex flex-wrap align-items-center justify-content-md-end gap-2 mb-0">
                                    <div class="d-flex align-items-center">
                                        <label for="filter" class="text-white text-xs font-weight-bold me-2 mb-0" style="white-space: nowrap;">Filter Date:</label>
                                        <select name="filter" id="filter" class="form-select form-select-sm bg-white border-0 text-dark font-weight-bold" style="border-radius: 0.5rem; width: auto; font-size: 0.75rem;" onchange="toggleCustomDates()">
                                            <option value="" {{ empty($page['filter']) ? 'selected' : '' }}>All Time</option>
                                            <option value="today" {{ ($page['filter'] ?? '') === 'today' ? 'selected' : '' }}>Today (On this day)</option>
                                            <option value="yesterday" {{ ($page['filter'] ?? '') === 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                                            <option value="last_week" {{ ($page['filter'] ?? '') === 'last_week' ? 'selected' : '' }}>Last Week (7 Days)</option>
                                            <option value="month" {{ ($page['filter'] ?? '') === 'month' ? 'selected' : '' }}>1 Month (30 Days)</option>
                                            <option value="last_month" {{ ($page['filter'] ?? '') === 'last_month' ? 'selected' : '' }}>Last Month</option>
                                            <option value="custom" {{ ($page['filter'] ?? '') === 'custom' ? 'selected' : '' }}>Custom Range</option>
                                        </select>
                                    </div>
                                    <div id="custom-date-inputs" class="align-items-center gap-2 {{ ($page['filter'] ?? '') === 'custom' ? 'd-flex' : 'd-none' }}">
                                        <input type="date" name="start_date" id="start_date" value="{{ $page['start_date'] ?? '' }}" class="form-control form-control-sm bg-white border-0 text-dark font-weight-bold" style="font-size: 0.75rem; border-radius: 0.5rem; width: 130px; height: 32px;">
                                        <span class="text-white text-xs">to</span>
                                        <input type="date" name="end_date" id="end_date" value="{{ $page['end_date'] ?? '' }}" class="form-control form-control-sm bg-white border-0 text-dark font-weight-bold" style="font-size: 0.75rem; border-radius: 0.5rem; width: 130px; height: 32px;">
                                        <button type="submit" class="btn btn-sm bg-gradient-warning mb-0 py-1.5 px-3 border-radius-md" style="font-size: 11px;">Apply</button>
                                    </div>
                                </form>

                                @if (\App\Models\SystemSetting::value('enable_book_history_deletion', 'true') === 'true' && auth()->user()->hasAnyRole(['super_admin', 'admin', 'location_manager', 'staff']))
                                    <form method="POST" action="{{ route('receipts.bulk-delete') }}" id="bulkDeleteForm" data-confirm="Are you sure you want to delete ALL bookings matching the active filter?" class="d-inline mb-0">
                                        @csrf
                                        <input type="hidden" name="filter" value="{{ $page['filter'] ?? '' }}">
                                        <input type="hidden" name="start_date" value="{{ $page['start_date'] ?? '' }}">
                                        <input type="hidden" name="end_date" value="{{ $page['end_date'] ?? '' }}">
                                        <button type="submit" class="btn btn-sm bg-gradient-danger mb-0 py-1.5 px-3 border-radius-md" style="font-size: 11px; white-space: nowrap;">
                                            <i class="fas fa-trash-alt me-1"></i>Delete All ({{ empty($page['filter']) ? 'All Time' : ucwords(str_replace('_', ' ', $page['filter'])) }})
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="card-body px-0 pt-0 pb-2">
                        {{-- Desktop View --}}
                        <div class="table-responsive d-none d-md-block">
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
                                                <div class="d-flex align-items-center justify-content-center gap-2">
                                                    <a href="{{ route('receipts.show', $receipt['reservation_id']) }}" class="btn btn-sm bg-gradient-dark mb-0 py-1.5 px-3 border-radius-md" style="font-size: 11px; white-space: nowrap;">
                                                        <i class="fas fa-eye me-1"></i>View Receipt
                                                    </a>
                                                    @if (($receipt['confirmable'] ?? false) && auth()->user()->hasAnyRole(['super_admin', 'admin', 'location_manager', 'staff']))
                                                        <form method="POST" action="{{ route('receipts.confirm', $receipt['reservation_id']) }}" data-confirm="Confirm this booking as booked?" class="d-inline">
                                                            @csrf
                                                            <button type="submit" class="btn btn-sm bg-gradient-success mb-0 py-1.5 px-3 border-radius-md" style="font-size: 11px; white-space: nowrap;">
                                                                <i class="fas fa-check me-1"></i>Confirm Booked
                                                            </button>
                                                        </form>
                                                    @endif
                                                    @if (\App\Models\SystemSetting::value('enable_book_history_deletion', 'true') === 'true')
                                                        <form method="POST" action="{{ route('receipts.destroy', $receipt['reservation_id']) }}" data-confirm="Are you sure you want to delete this booking?" class="d-inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-sm bg-gradient-danger mb-0 py-1.5 px-3 border-radius-md" style="font-size: 11px; white-space: nowrap;">
                                                                <i class="fas fa-trash me-1"></i>Delete
                                                            </button>
                                                        </form>
                                                    @endif
                                                </div>
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

                        {{-- Mobile View --}}
                        <div class="d-block d-md-none p-3">
                            @forelse (($page['receiptsList'] ?? []) as $receipt)
                                <div class="card p-3 mb-3 border border-radius-md bg-white shadow-sm hover-shadow-sm transition-all" style="border: 1px solid #f0f2f5 !important;">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <div class="d-flex align-items-center">
                                            <div class="icon icon-shape bg-gradient-info text-center border-radius-md me-2 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                                                <i class="fas fa-receipt text-white text-xs"></i>
                                            </div>
                                            <span class="text-xs font-weight-bold text-dark">{{ $receipt['reservation_code'] }}</span>
                                        </div>
                                        <span class="badge badge-sm bg-gradient-{{ $receipt['color'] }} px-2 py-1" style="border-radius: 0.5rem; font-size: 10px;">
                                            {{ $receipt['status'] }}
                                        </span>
                                    </div>
                                    <div class="mb-3 pt-2">
                                        @if (auth()->user()->hasAnyRole(['super_admin', 'admin', 'location_manager', 'staff']))
                                            <p class="text-xs text-dark mb-1.5 d-flex align-items-center">
                                                <img src="{{ $receipt['photo_url'] }}" class="avatar avatar-xxs rounded-circle me-2" style="width: 18px; height: 18px;" alt="{{ $receipt['customer'] }}">
                                                <span class="text-secondary me-1">Customer:</span> <strong>{{ $receipt['customer'] }}</strong>
                                            </p>
                                        @endif
                                        <p class="text-xs text-dark mb-1.5">
                                            <i class="fas fa-map-marker-alt text-secondary me-2"></i>
                                            <span class="text-secondary">Court:</span> <strong>{{ $receipt['court'] }}</strong>
                                        </p>
                                        <p class="text-xs text-dark mb-1.5">
                                            <i class="far fa-clock text-secondary me-2"></i>
                                            <span class="text-secondary">Sched:</span> <strong>{{ $receipt['schedule'] }}</strong>
                                        </p>
                                        <p class="text-xs text-dark mb-0">
                                            <i class="fas fa-key text-secondary me-2"></i>
                                            <span class="text-secondary">Ref:</span> <code>{{ $receipt['reference'] }}</code>
                                        </p>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                                        <div>
                                            <span class="text-xxs text-secondary d-block">Amount Paid</span>
                                            <span class="text-sm font-weight-bolder text-dark">{{ $receipt['amount'] }}</span>
                                        </div>
                                        <div class="d-flex gap-2">
                                            <a href="{{ route('receipts.show', $receipt['reservation_id']) }}" class="btn btn-sm bg-gradient-dark mb-0 py-1.5 px-3 border-radius-md" style="font-size: 11px; white-space: nowrap;">
                                                <i class="fas fa-eye me-1"></i>View
                                            </a>
                                            @if (($receipt['confirmable'] ?? false) && auth()->user()->hasAnyRole(['super_admin', 'admin', 'location_manager', 'staff']))
                                                <form method="POST" action="{{ route('receipts.confirm', $receipt['reservation_id']) }}" data-confirm="Confirm this booking as booked?" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm bg-gradient-success mb-0 py-1.5 px-3 border-radius-md" style="font-size: 11px; white-space: nowrap;">
                                                        <i class="fas fa-check me-1"></i>Confirm
                                                    </button>
                                                </form>
                                            @endif
                                            @if (\App\Models\SystemSetting::value('enable_book_history_deletion', 'true') === 'true')
                                                <form method="POST" action="{{ route('receipts.destroy', $receipt['reservation_id']) }}" data-confirm="Are you sure you want to delete this booking?" class="d-inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm bg-gradient-danger mb-0 py-1.5 px-3 border-radius-md" style="font-size: 11px; white-space: nowrap;">
                                                        <i class="fas fa-trash me-1"></i>Delete
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-5 text-secondary">
                                    <i class="fas fa-receipt fa-2x mb-3 d-block opacity-4"></i>
                                    <span class="text-sm">No approved bookings found in history.</span>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script>
            function toggleCustomDates() {
                const filter = document.getElementById('filter').value;
                const customDiv = document.getElementById('custom-date-inputs');
                if (filter === 'custom') {
                    customDiv.classList.remove('d-none');
                    customDiv.classList.add('d-flex');
                } else {
                    customDiv.classList.add('d-none');
                    customDiv.classList.remove('d-flex');
                    document.getElementById('filterForm').submit();
                }
            }
        </script>
    @endif

    {{-- =========================================================
         GENERAL SETTINGS (admin) - system-wide configuration
         ========================================================= --}}
    @if ($module === 'general-settings' && auth()->user()?->hasAnyRole(['super_admin','admin']))
        @php
            $publicSettings = $page['publicSiteSettings'] ?? [];
            $canEditSettings = auth()->user()->hasRole('super_admin');
        @endphp
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header pb-0">
                        <h6>General Settings</h6>
                        <p class="text-sm mb-0">System-wide configuration. Set the opening time, floating contact links, and Book History bulk options.</p>
                    </div>
                    <div class="card-body">
                        @unless ($canEditSettings)
                            <div class="alert alert-secondary text-white mb-4 py-2">
                                <i class="fas fa-lock me-1"></i>Only a Super Admin can change these settings. You can view the current values below.
                            </div>
                        @endunless
                        <form method="POST" action="{{ route('admin.settings.public-site.update') }}">
                            @csrf
                            <fieldset @disabled(! $canEditSettings) style="border:0; padding:0; margin:0;">
                            <div class="row">
                                <div class="col-12 mb-2">
                                    <p class="text-xs text-uppercase text-secondary mb-2">Opening Time</p>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label text-xs">Open From</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-door-open"></i></span>
                                        <input type="time" name="public_playing_open_time" class="form-control" value="{{ old('public_playing_open_time', $publicSettings['public_playing_open_time'] ?? '07:00') }}" required>
                                    </div>
                                </div>
                                <div class="col-md-3 mb-3">
                                    <label class="form-label text-xs">Open Until</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-moon"></i></span>
                                        <input type="time" name="public_playing_close_time" class="form-control" value="{{ old('public_playing_close_time', $publicSettings['public_playing_close_time'] ?? '00:00') }}" required>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="alert alert-info text-white mb-0 py-2">
                                        Example: 7:00 AM to 12:00 AM means 1:00 AM to 6:00 AM is closed and will not show as available.
                                    </div>
                                </div>

                                <div class="col-12 mb-2">
                                    <p class="text-xs text-uppercase text-secondary mb-2">Floating Contact Links</p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-xs"><i class="fab fa-facebook-f text-info me-1"></i>Facebook Link</label>
                                    <input type="url" name="public_facebook_url" class="form-control" value="{{ old('public_facebook_url', $publicSettings['public_facebook_url'] ?? '') }}" placeholder="https://facebook.com/...">
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label class="form-label text-xs"><i class="fas fa-envelope text-primary me-1"></i>Email</label>
                                    <input type="email" name="public_contact_email" class="form-control" value="{{ old('public_contact_email', $publicSettings['public_contact_email'] ?? '') }}" placeholder="name@example.com">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label text-xs"><i class="fas fa-phone text-success me-1"></i>Phone</label>
                                    <input type="text" name="public_contact_phone" class="form-control" value="{{ old('public_contact_phone', $publicSettings['public_contact_phone'] ?? '') }}" placeholder="09XXXXXXXXX">
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label text-xs"><i class="fas fa-code text-dark me-1"></i>Highlighted Name</label>
                                    <input type="text" name="public_developer_name" class="form-control" value="{{ old('public_developer_name', $publicSettings['public_developer_name'] ?? 'RestBack') }}" placeholder="RestBack">
                                </div>

                                <div class="col-md-8 mb-3">
                                    <label class="form-label text-xs"><i class="fas fa-link text-secondary me-1"></i>Highlighted Name Link</label>
                                    <input type="url" name="public_developer_url" class="form-control" value="{{ old('public_developer_url', $publicSettings['public_developer_url'] ?? '') }}" placeholder="https://...">
                                </div>

                                <div class="col-12 mb-3">
                                    <label class="form-label text-xs"><i class="fas fa-file-contract text-secondary me-1"></i>Booking Terms & Conditions</label>
                                    <textarea name="booking_terms_and_conditions" class="form-control" rows="5" placeholder="Enter terms & conditions for booking...">{{ old('booking_terms_and_conditions', $publicSettings['booking_terms_and_conditions'] ?? '') }}</textarea>
                                </div>

                                <div class="col-12 mb-2">
                                    <p class="text-xs text-uppercase text-secondary mb-2">System Controls</p>
                                </div>
                                <div class="col-md-12 mb-3 d-flex align-items-center">
                                    <div class="form-check form-switch ps-0">
                                        <input class="form-check-input ms-auto" type="checkbox" name="enable_lunch_break" id="enable_lunch_break" value="true" {{ filter_var($publicSettings['enable_lunch_break'] ?? true, FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
                                        <label class="form-check-label text-xs font-weight-bold ms-2 mb-0" for="enable_lunch_break">
                                            <i class="fas fa-utensils text-info me-1"></i>Enable Court Lunch Break (12:00 PM - 12:30 PM)
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-8 mb-3 d-flex align-items-center">
                                    <div class="form-check form-switch ps-0">
                                        <input class="form-check-input ms-auto" type="checkbox" name="enable_book_history_deletion" id="enable_book_history_deletion" value="true" {{ filter_var(\App\Models\SystemSetting::value('enable_book_history_deletion', 'true'), FILTER_VALIDATE_BOOLEAN) ? 'checked' : '' }}>
                                        <label class="form-check-label text-xs font-weight-bold ms-2 mb-0" for="enable_book_history_deletion">
                                            <i class="fas fa-trash-alt text-danger me-1"></i>Enable Delete/Bulk Delete on Book History &amp; Sales
                                        </label>
                                    </div>
                                </div>
                                @if ($canEditSettings)
                                    <div class="col-md-4 mb-3 d-flex align-items-end">
                                        <button type="submit" class="btn bg-gradient-dark mb-0 w-100">Save Settings</button>
                                    </div>
                                @endif
                            </div>
                            </fieldset>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- =========================================================
         SALES (admin) - full booking & sales history + export
         ========================================================= --}}
    @if ($module === 'sales' && auth()->user()?->hasAnyRole(['super_admin','admin']))
        @include('modules.partials.sales', [
            'salesList' => $page['receiptsList'] ?? [],
            'filter' => $page['filter'] ?? '',
            'startDate' => $page['start_date'] ?? '',
            'endDate' => $page['end_date'] ?? '',
        ])
    @endif

</x-app-layout>
