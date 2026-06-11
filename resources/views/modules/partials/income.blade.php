{{-- Income Overview - Super Admin Only --}}
@php
    $todayStart = now()->toDateString();
    $todayEnd = now()->toDateString();
    $weekStart = now()->startOfWeek()->toDateString();
    $weekEnd = now()->endOfWeek()->toDateString();
    $monthStart = now()->startOfMonth()->toDateString();
    $monthEnd = now()->endOfMonth()->toDateString();
    $currentStart = $incomeStart ?? $monthStart;
    $currentEnd = $incomeEnd ?? $monthEnd;
    $isToday = $currentStart === $todayStart && $currentEnd === $todayEnd;
    $isThisWeek = $currentStart === $weekStart && $currentEnd === $weekEnd;
    $isThisMonth = $currentStart === $monthStart && $currentEnd === $monthEnd;
@endphp

<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header pb-0">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2">
                    <div>
                        <p class="text-uppercase text-info text-xxs font-weight-bolder mb-1">Financial Overview</p>
                        <h6 class="mb-0">Income Report</h6>
                        <p class="text-sm text-secondary mb-1">Track bookings, paid income, and customer names for any date range.</p>
                        <span class="badge bg-gradient-info text-white text-xxs">
                            <i class="fas fa-calendar-alt me-1"></i>{{ $incomeMonth }}
                        </span>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('modules.show', 'income') }}?start_date={{ $todayStart }}&end_date={{ $todayEnd }}"
                           class="btn btn-sm {{ $isToday ? 'btn-dark' : 'btn-outline-secondary' }} mb-0">Today</a>
                        <a href="{{ route('modules.show', 'income') }}?start_date={{ $weekStart }}&end_date={{ $weekEnd }}"
                           class="btn btn-sm {{ $isThisWeek ? 'btn-dark' : 'btn-outline-secondary' }} mb-0">This Week</a>
                        <a href="{{ route('modules.show', 'income') }}?start_date={{ $monthStart }}&end_date={{ $monthEnd }}"
                           class="btn btn-sm {{ $isThisMonth ? 'btn-dark' : 'btn-outline-secondary' }} mb-0">This Month</a>
                    </div>
                </div>
            </div>
            <div class="card-body pt-3">
                {{-- Date Range Filter Form --}}
                <form method="GET" action="{{ route('modules.show', 'income') }}" class="mb-4">
                    <div class="row align-items-end g-3">
                        <div class="col-md-3">
                            <label for="incomeStartDate" class="form-label text-uppercase text-xxs font-weight-bolder text-secondary">Start Date</label>
                            <input type="date" id="incomeStartDate" name="start_date" class="form-control" value="{{ $currentStart }}" max="{{ $todayEnd }}">
                        </div>
                        <div class="col-md-3">
                            <label for="incomeEndDate" class="form-label text-uppercase text-xxs font-weight-bolder text-secondary">End Date</label>
                            <input type="date" id="incomeEndDate" name="end_date" class="form-control" value="{{ $currentEnd }}" max="{{ $todayEnd }}">
                        </div>
                        <div class="col-md-3">
                            <div class="border-start border-info border-3 ps-3">
                                <p class="text-uppercase text-xxs font-weight-bolder text-info mb-1">Days With Bookings</p>
                                <h5 class="mb-0">{{ count(collect($incomeCustomers)->groupBy('date')) }}</h5>
                            </div>
                        </div>
                        <div class="col-md-3 text-md-end">
                            <button type="submit" class="btn bg-gradient-dark text-white mb-0">
                                <i class="fas fa-search me-2"></i>Generate Report
                            </button>
                        </div>
                    </div>
                </form>

                {{-- Summary Cards --}}
                <div class="row mb-4">
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card bg-gradient-success text-white">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <div class="icon icon-shape bg-white shadow text-center border-radius-md me-3">
                                        <i class="fas fa-peso-sign text-success text-lg opacity-10" aria-hidden="true"></i>
                                    </div>
                                    <div>
                                        <p class="text-xs text-white mb-0 opacity-8">Paid Income</p>
                                        <h5 class="text-white font-weight-bolder mb-0">PHP {{ number_format($monthlyIncome, 2) }}</h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card bg-gradient-info text-white">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <div class="icon icon-shape bg-white shadow text-center border-radius-md me-3">
                                        <i class="fas fa-calendar-check text-info text-lg opacity-10" aria-hidden="true"></i>
                                    </div>
                                    <div>
                                        <p class="text-xs text-white mb-0 opacity-8">Paid Bookings</p>
                                        <h5 class="text-white font-weight-bolder mb-0">{{ $totalBookings }}</h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="card bg-gradient-dark text-white">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center">
                                    <div class="icon icon-shape bg-white shadow text-center border-radius-md me-3">
                                        <i class="fas fa-users text-dark text-lg opacity-10" aria-hidden="true"></i>
                                    </div>
                                    <div>
                                        <p class="text-xs text-white mb-0 opacity-8">Unique Customers</p>
                                        <h5 class="text-white font-weight-bolder mb-0">{{ count(collect($incomeCustomers)->unique('email')) }}</h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Customer Transactions Table --}}
                <h6 class="text-uppercase text-xs font-weight-bolder opacity-6 mb-3">Customers Who Availed</h6>
                @if (count($incomeCustomers) > 0)
                    <div class="table-responsive">
                        <table class="table align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Customer</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Booking</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Date & Time</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Court</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2 text-end">Amount</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($incomeCustomers as $customer)
                                    <tr>
                                        <td>
                                            <div class="d-flex px-2 py-1">
                                                <div class="d-flex flex-column justify-content-center">
                                                    <h6 class="mb-0 text-sm">{{ $customer['name'] }}</h6>
                                                    <p class="text-xs text-secondary mb-0">{{ $customer['email'] }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-xs font-weight-bold">{{ $customer['reservation_code'] }}</span>
                                        </td>
                                        <td>
                                            <span class="text-xs font-weight-bold">{{ $customer['date'] }}</span>
                                            <br>
                                            <span class="text-xs text-secondary">{{ $customer['time'] }}</span>
                                        </td>
                                        <td>
                                            <span class="text-xs">{{ $customer['court'] }}</span>
                                        </td>
                                        <td class="text-end">
                                            <span class="text-xs font-weight-bold text-success">PHP {{ number_format($customer['amount'], 2) }}</span>
                                        </td>
                                        <td>
                                            @php
                                                $badgeColor = match($customer['status']) {
                                                    'confirmed' => 'bg-gradient-success',
                                                    'completed' => 'bg-gradient-info',
                                                    'checked_in', 'ongoing' => 'bg-gradient-primary',
                                                    default => 'bg-gradient-secondary',
                                                };
                                            @endphp
                                            <span class="badge badge-sm {{ $badgeColor }}">{{ str_replace('_', ' ', ucfirst($customer['status'])) }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-sm text-secondary text-center py-4 mb-0">No paid bookings in the selected date range.</p>
                @endif
            </div>
        </div>
    </div>
</div>
