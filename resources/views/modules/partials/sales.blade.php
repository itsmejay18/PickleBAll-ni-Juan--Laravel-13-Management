@php
    /** @var array $salesList */
    $filter = $filter ?? '';
    $startDate = $startDate ?? '';
    $endDate = $endDate ?? '';

    // Export uses a from/to range. Translate the active quick-filter into concrete dates.
    $exportFrom = now()->startOfMonth()->toDateString();
    $exportTo = now()->toDateString();

    if ($filter === 'today') {
        $exportFrom = $exportTo = now()->toDateString();
    } elseif ($filter === 'yesterday') {
        $exportFrom = $exportTo = now()->subDay()->toDateString();
    } elseif ($filter === 'last_week') {
        $exportFrom = now()->subDays(6)->toDateString();
        $exportTo = now()->toDateString();
    } elseif ($filter === 'month') {
        $exportFrom = now()->subDays(29)->toDateString();
        $exportTo = now()->toDateString();
    } elseif ($filter === 'last_month') {
        $exportFrom = now()->subMonth()->startOfMonth()->toDateString();
        $exportTo = now()->subMonth()->endOfMonth()->toDateString();
    } elseif ($filter === 'custom') {
        $exportFrom = $startDate ?: $exportFrom;
        $exportTo = $endDate ?: $exportTo;
    }
@endphp

<div class="row mb-4">
    <div class="col-12">
        <div class="card shadow-lg border-0 bg-white" style="border-radius: 1rem; overflow: hidden;">
            <div class="card-header pb-3 bg-gradient-dark position-relative z-index-1">
                <div class="row align-items-center">
                    <div class="col-lg-5 col-md-4 mb-3 mb-md-0">
                        <h5 class="text-white font-weight-bolder mb-1">
                            <i class="fas fa-cash-register me-2 text-warning"></i>Sales History
                        </h5>
                        <p class="text-white text-xs opacity-8 mb-0">
                            All customer bookings and payments. Filter by date, then export to CSV or PDF.
                        </p>
                    </div>
                    <div class="col-lg-7 col-md-8 d-flex flex-wrap align-items-center justify-content-md-end gap-2">
                        <form method="GET" action="{{ route('modules.show', 'sales') }}" id="salesFilterForm" class="d-flex flex-wrap align-items-center justify-content-md-end gap-2 mb-0">
                            <div class="d-flex align-items-center">
                                <label for="salesFilter" class="text-white text-xs font-weight-bold me-2 mb-0" style="white-space: nowrap;">Filter Date:</label>
                                <select name="filter" id="salesFilter" class="form-select form-select-sm bg-white border-0 text-dark font-weight-bold" style="border-radius: 0.5rem; width: auto; font-size: 0.75rem;" onchange="toggleSalesCustomDates()">
                                    <option value="" {{ empty($filter) ? 'selected' : '' }}>All Time</option>
                                    <option value="today" {{ $filter === 'today' ? 'selected' : '' }}>Today</option>
                                    <option value="yesterday" {{ $filter === 'yesterday' ? 'selected' : '' }}>Yesterday</option>
                                    <option value="last_week" {{ $filter === 'last_week' ? 'selected' : '' }}>Last Week (7 Days)</option>
                                    <option value="month" {{ $filter === 'month' ? 'selected' : '' }}>1 Month (30 Days)</option>
                                    <option value="last_month" {{ $filter === 'last_month' ? 'selected' : '' }}>Last Month</option>
                                    <option value="custom" {{ $filter === 'custom' ? 'selected' : '' }}>Custom Range</option>
                                </select>
                            </div>
                            <div id="sales-custom-date-inputs" class="align-items-center gap-2 {{ $filter === 'custom' ? 'd-flex' : 'd-none' }}">
                                <input type="date" name="start_date" id="sales_start_date" value="{{ $startDate }}" class="form-control form-control-sm bg-white border-0 text-dark font-weight-bold" style="font-size: 0.75rem; border-radius: 0.5rem; width: 130px; height: 32px;">
                                <span class="text-white text-xs">to</span>
                                <input type="date" name="end_date" id="sales_end_date" value="{{ $endDate }}" class="form-control form-control-sm bg-white border-0 text-dark font-weight-bold" style="font-size: 0.75rem; border-radius: 0.5rem; width: 130px; height: 32px;">
                                <button type="submit" class="btn btn-sm bg-gradient-warning mb-0 py-1.5 px-3 border-radius-md" style="font-size: 11px;">Apply</button>
                            </div>
                        </form>

                        <a href="{{ route('reports.export', 'sales') }}?from={{ $exportFrom }}&to={{ $exportTo }}" class="btn btn-sm bg-gradient-info mb-0 py-1.5 px-3 border-radius-md" style="font-size: 11px; white-space: nowrap;">
                            <i class="fas fa-file-csv me-1"></i>Export CSV
                        </a>
                        <a href="{{ route('reports.sales.pdf') }}?from={{ $exportFrom }}&to={{ $exportTo }}" target="_blank" class="btn btn-sm bg-gradient-danger mb-0 py-1.5 px-3 border-radius-md" style="font-size: 11px; white-space: nowrap;">
                            <i class="fas fa-file-pdf me-1"></i>Export PDF
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body px-0 pt-0 pb-2">
                <div class="table-responsive">
                    <table class="table align-items-center mb-0">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Reservation Code</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Customer</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Location &amp; Court</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Schedule</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Amount</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Status</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($salesList as $sale)
                                <tr class="hover-shadow-sm transition-all">
                                    <td>
                                        <div class="d-flex px-3 py-2">
                                            <div class="icon icon-shape bg-gradient-info text-center border-radius-md me-3 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                                                <i class="fas fa-receipt text-white text-sm"></i>
                                            </div>
                                            <div class="d-flex flex-column justify-content-center">
                                                <h6 class="mb-0 text-sm font-weight-bold text-dark">{{ $sale['reservation_code'] }}</h6>
                                                <p class="text-xxs text-secondary mb-0">Ref: {{ $sale['reference'] }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center py-1">
                                            <img src="{{ $sale['photo_url'] }}" class="avatar avatar-xs rounded-circle me-2" alt="{{ $sale['customer'] }}">
                                            <span class="text-xs font-weight-bold text-dark">{{ $sale['customer'] }}</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="text-xs font-weight-bold text-dark"><i class="fas fa-map-marker-alt me-1 text-secondary"></i>{{ $sale['court'] }}</span>
                                    </td>
                                    <td>
                                        <span class="text-xs text-secondary"><i class="far fa-clock me-1"></i>{{ $sale['schedule'] }}</span>
                                    </td>
                                    <td class="align-middle text-center">
                                        <span class="text-sm font-weight-bolder text-dark">{{ $sale['amount'] }}</span>
                                    </td>
                                    <td class="align-middle text-center text-sm">
                                        <span class="badge badge-sm bg-gradient-{{ $sale['color'] }} px-2 py-1" style="border-radius: 0.5rem;">
                                            {{ $sale['status'] }}
                                        </span>
                                    </td>
                                    <td class="align-middle text-center">
                                        <div class="d-flex align-items-center justify-content-center gap-2">
                                            <a href="{{ route('receipts.show', $sale['reservation_id']) }}" class="btn btn-sm bg-gradient-dark mb-0 py-1.5 px-3 border-radius-md" style="font-size: 11px; white-space: nowrap;">
                                                <i class="fas fa-eye me-1"></i>View Receipt
                                            </a>
                                            @if (\App\Models\SystemSetting::value('enable_book_history_deletion', 'true') === 'true')
                                                <form method="POST" action="{{ route('receipts.destroy', $sale['reservation_id']) }}" data-confirm="Are you sure you want to delete this booking?" class="d-inline">
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
                                        <i class="fas fa-cash-register fa-2x mb-3 d-block opacity-4"></i>
                                        <span class="text-sm">No sales found for the selected range.</span>
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
<script>
    function toggleSalesCustomDates() {
        const filter = document.getElementById('salesFilter').value;
        const customDiv = document.getElementById('sales-custom-date-inputs');
        if (filter === 'custom') {
            customDiv.classList.remove('d-none');
            customDiv.classList.add('d-flex');
        } else {
            customDiv.classList.add('d-none');
            customDiv.classList.remove('d-flex');
            document.getElementById('salesFilterForm').submit();
        }
    }
</script>
