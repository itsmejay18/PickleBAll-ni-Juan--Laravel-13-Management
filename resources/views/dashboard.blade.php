@php
    $dashboardTitle = match ($role) {
        'admin' => 'Admin Command Center',
        'staff' => 'Staff Operations',
        default => 'My Bookings',
    };

    $statStyles = [
        ['color' => 'primary', 'change' => '+55%'],
        ['color' => 'dark', 'change' => '+12%'],
        ['color' => 'dark', 'change' => '+15%'],
        ['color' => 'dark', 'change' => '+90%'],
    ];

    $healthRows = $reservationHealth ?? [];
    $rows = $operationRows ?? [];
@endphp

<x-app-layout>
    <x-slot name="header">{{ $dashboardTitle }}</x-slot>

    <div class="row">
        <div class="col-lg-6 col-12">
            <div class="row">
                @foreach ($metrics as $metric)
                    @php($style = $statStyles[$loop->index] ?? $statStyles[0])
                    <div class="col-lg-6 col-md-6 col-12 {{ $loop->index > 1 ? 'mt-4' : ($loop->index % 2 === 1 ? 'mt-4 mt-md-0' : '') }}">
                        <div class="card pbj-stat-card">
                            <span class="mask bg-{{ $style['color'] }} opacity-10 border-radius-lg"></span>
                            <div class="card-body p-3 position-relative">
                                <div class="row">
                                    <div class="col-8 text-start">
                                        <div class="icon icon-shape bg-white shadow text-center border-radius-2xl">
                                            <i class="fas {{ $metric['icon'] ?? 'fa-chart-pie' }} text-dark text-gradient text-lg opacity-10" aria-hidden="true"></i>
                                        </div>
                                        <h5 class="text-white font-weight-bolder mb-0 mt-3">{{ $metric['value'] }}</h5>
                                        <span class="text-white text-sm">{{ $metric['label'] }}</span>
                                    </div>
                                    <div class="col-4">
                                        <div class="{{ $loop->index % 2 ? 'dropstart' : 'dropdown' }} text-end mb-6">
                                            <a href="javascript:;" class="cursor-pointer" data-bs-toggle="dropdown" aria-expanded="false">
                                                <i class="fa fa-ellipsis-h text-white"></i>
                                            </a>
                                            <ul class="dropdown-menu px-2 py-3">
                                                <li><a class="dropdown-item border-radius-md" href="{{ route('modules.show', 'reports') }}">View report</a></li>
                                                <li><a class="dropdown-item border-radius-md" href="{{ route('modules.show', 'book-court') }}">New booking</a></li>
                                            </ul>
                                        </div>
                                        <p class="text-white text-sm text-end font-weight-bolder mt-auto mb-0">{{ $metric['change'] ?? $style['change'] }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="col-lg-6 col-12 mt-4 mt-lg-0">
            <div class="card shadow h-100">
                <div class="card-header pb-0 p-3">
                    <h6 class="mb-0">Reservation Health</h6>
                </div>
                <div class="card-body pb-0 p-3">
                    <ul class="list-group">
                        @foreach ($healthRows as $row)
                            <li class="list-group-item border-0 d-flex align-items-center px-0 {{ $loop->last ? 'mb-0' : 'mb-2' }}">
                                <div class="w-100">
                                    <div class="d-flex mb-2">
                                        <span class="me-2 text-sm font-weight-bold text-dark">{{ $row['label'] }}</span>
                                        <span class="ms-auto text-sm font-weight-bold">{{ $row['value'] }}%</span>
                                    </div>
                                    <div class="progress progress-md">
                                        <div class="progress-bar bg-primary" style="width: {{ $row['value'] }}%" role="progressbar" aria-valuenow="{{ $row['value'] }}" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
                <div class="card-footer pt-0 p-3 d-flex align-items-center">
                    <div class="w-60">
                        <p class="text-sm mb-0">Court activity, payment status, and blocked slots are calculated from live reservation and maintenance records.</p>
                    </div>
                    <div class="w-40 text-end">
                        <a class="btn btn-dark mb-0 text-end" href="{{ route('modules.show', 'reports') }}">View reports</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row my-4">
        <div class="col-lg-8 col-md-6 mb-md-0 mb-4">
            <div class="card">
                <div class="card-header pb-0">
                    <div class="row">
                        <div class="col-lg-6 col-7">
                            <h6>Operations</h6>
                            <p class="text-sm mb-0">
                                <i class="fa fa-check text-info" aria-hidden="true"></i>
                                <span class="font-weight-bold ms-1">{{ count($calendarSlots) }} slots</span> visible today
                            </p>
                        </div>
                        <div class="col-lg-6 col-5 my-auto text-end">
                            <div class="dropdown float-lg-end pe-4">
                                <a class="cursor-pointer" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fa fa-ellipsis-v text-secondary"></i>
                                </a>
                                <ul class="dropdown-menu px-2 py-3 ms-sm-n4 ms-n5">
                                    <li><a class="dropdown-item border-radius-md" href="{{ route('modules.show', 'book-court') }}">Book court</a></li>
                                    <li><a class="dropdown-item border-radius-md" href="{{ route('modules.show', 'check-ins') }}">Open check-in</a></li>
                                    <li><a class="dropdown-item border-radius-md" href="{{ route('modules.show', 'equipment') }}">Review equipment</a></li>
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
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Workflow</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Team</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Window</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Completion</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($rows as $row)
                                    <tr>
                                        <td>
                                            <div class="d-flex px-2 py-1">
                                                <div class="avatar avatar-sm bg-gradient-{{ $row['color'] }} me-3 d-flex align-items-center justify-content-center">
                                                    <i class="fas {{ $row['icon'] }} text-white text-sm"></i>
                                                </div>
                                                <div class="d-flex flex-column justify-content-center">
                                                    <h6 class="mb-0 text-sm">{{ $row['name'] }}</h6>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="avatar-group mt-2">
                                                <a href="javascript:;" class="avatar avatar-xs rounded-circle" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Admin">
                                                    <img src="{{ asset('images/branding.png') }}" alt="Admin">
                                                </a>
                                                <a href="javascript:;" class="avatar avatar-xs rounded-circle" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Staff">
                                                    <img src="{{ asset('soft-ui-dashboard-main/assets/img/team-2.jpg') }}" alt="Staff">
                                                </a>
                                                <a href="javascript:;" class="avatar avatar-xs rounded-circle" data-bs-toggle="tooltip" data-bs-placement="bottom" title="Customer">
                                                    <img src="{{ asset('soft-ui-dashboard-main/assets/img/team-3.jpg') }}" alt="Customer">
                                                </a>
                                            </div>
                                        </td>
                                        <td class="align-middle text-center text-sm">
                                            <span class="text-xs font-weight-bold">{{ $row['budget'] }}</span>
                                        </td>
                                        <td class="align-middle">
                                            <div class="progress-wrapper w-75 mx-auto">
                                                <div class="progress-info">
                                                    <div class="progress-percentage">
                                                        <span class="text-xs font-weight-bold">{{ $row['progress'] }}%</span>
                                                    </div>
                                                </div>
                                                <div class="progress">
                                                    <div class="progress-bar bg-gradient-{{ $row['color'] }}" style="width: {{ $row['progress'] }}%" role="progressbar" aria-valuenow="{{ $row['progress'] }}" aria-valuemin="0" aria-valuemax="100"></div>
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

        <div class="col-lg-4 col-md-6">
            <div class="card h-100">
                <div class="card-header pb-0">
                    <h6>Orders overview</h6>
                    <p class="text-sm">
                        <i class="fa {{ ($ordersChange ?? 0) >= 0 ? 'fa-arrow-up text-success' : 'fa-arrow-down text-danger' }}" aria-hidden="true"></i>
                        <span class="font-weight-bold">{{ abs($ordersChange ?? 0) }}%</span> this month
                    </p>
                </div>
                <div class="card-body p-3">
                    <div class="timeline timeline-one-side">
                        @foreach ($workQueue as $item)
                            <div class="timeline-block {{ $loop->last ? '' : 'mb-3' }}">
                                <span class="timeline-step">
                                    <i class="fa fa-check text-{{ $loop->first ? 'success' : ($loop->iteration === 2 ? 'info' : 'dark') }} text-gradient"></i>
                                </span>
                                <div class="timeline-content">
                                    <h6 class="text-dark text-sm font-weight-bold mb-0">{{ $item['title'] }}</h6>
                                    <p class="text-secondary font-weight-bold text-xs mt-1 mb-0">{{ $item['description'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
