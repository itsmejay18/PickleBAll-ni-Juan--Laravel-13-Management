@php
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
            @php($style = $cardStyles[$loop->index] ?? $cardStyles[0])
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
                        <h6>Create Booking Transaction</h6>
                        <p class="text-sm mb-0">Choose a court, add paddle or ball rentals, then upload GCash proof after the booking is created.</p>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('bookings.store') }}">
                            @csrf

                            <div class="row">
                                <div class="col-lg-4 col-md-6 mb-3">
                                    <label class="form-label text-xs">Location</label>
                                    <select name="location_id" class="form-control" required>
                                        @foreach (($page['bookingLocations'] ?? []) as $location)
                                            <option value="{{ $location->id }}" @selected((string) old('location_id') === (string) $location->id)>
                                                {{ $location->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-lg-4 col-md-6 mb-3">
                                    <label class="form-label text-xs">Court</label>
                                    <select name="court_id" class="form-control" required>
                                        @foreach (($page['bookingCourts'] ?? []) as $court)
                                            <option value="{{ $court['id'] }}" @selected((string) old('court_id') === (string) $court['id'])>
                                                {{ $court['label'] }} - PHP {{ number_format($court['rate'], 2) }}/hr
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-lg-4 col-md-6 mb-3">
                                    <label class="form-label text-xs">Date</label>
                                    <input type="date" name="reservation_date" class="form-control" value="{{ old('reservation_date', now()->addDay()->toDateString()) }}" min="{{ now()->toDateString() }}" required>
                                </div>

                                <div class="col-lg-2 col-md-6 mb-3">
                                    <label class="form-label text-xs">Start</label>
                                    <input type="time" name="start_time" class="form-control" value="{{ old('start_time', '08:00') }}" required>
                                </div>

                                <div class="col-lg-2 col-md-6 mb-3">
                                    <label class="form-label text-xs">End</label>
                                    <input type="time" name="end_time" class="form-control" value="{{ old('end_time', '10:00') }}" required>
                                </div>

                                <div class="col-lg-8 col-md-12 mb-3">
                                    <label class="form-label text-xs">Special requests</label>
                                    <input type="text" name="special_requests" class="form-control" value="{{ old('special_requests') }}" placeholder="Optional note for the court team">
                                </div>
                            </div>

                            <div class="row">
                                @forelse (($page['bookingEquipment'] ?? []) as $item)
                                    <div class="col-lg-4 col-md-6 mb-3">
                                        <label class="form-label text-xs">{{ $item['name'] }}</label>
                                        <div class="input-group">
                                            <span class="input-group-text">Qty</span>
                                            <input type="number" name="equipment[{{ $item['id'] }}]" class="form-control" min="0" max="{{ min($item['max'], $item['available']) }}" value="{{ old('equipment.'.$item['id'], 0) }}">
                                        </div>
                                        <p class="text-xs text-secondary mb-0 mt-1">
                                            {{ $item['available'] }} available - PHP {{ number_format($item['price'], 2) }}/unit
                                            @if ($item['deposit'] > 0)
                                                - PHP {{ number_format($item['deposit'], 2) }} deposit
                                            @endif
                                        </p>
                                    </div>
                                @empty
                                    <div class="col-12">
                                        <p class="text-sm text-secondary mb-3">No rentable equipment is available yet.</p>
                                    </div>
                                @endforelse
                            </div>

                            <button type="submit" class="btn bg-gradient-info mb-0">Create booking</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
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
                </div>
            @else
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
         Generic feature/owner table - rendered for all module pages
         ========================================================= --}}
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
                                    @php($progress = $row['progress'] ?? [25, 40, 60, 100][$loop->index % 4])
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
                                            @php($owners = count($row['owners'] ?? []) ? $row['owners'] : [['name' => $row['objective'] ?? $page['owner'], 'photo_url' => asset('images/branding.png')]])
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
</x-app-layout>
