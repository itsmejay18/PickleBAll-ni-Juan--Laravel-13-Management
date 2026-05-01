<x-app-layout>
    <x-slot name="header">
        {{ match ($role) {
            'admin' => 'Admin Command Center',
            'staff' => 'Staff Operations',
            default => 'My Bookings',
        } }}
    </x-slot>

    <div class="row">
        @foreach ($metrics as $metric)
            <div class="col-xl-3 col-sm-6 mb-xl-0 mb-4">
                <div class="card">
                    <div class="card-body p-3">
                        <div class="row">
                            <div class="col-8">
                                <div class="numbers">
                                    <p class="text-sm mb-0 text-capitalize font-weight-bold">{{ $metric['label'] }}</p>
                                    <h5 class="font-weight-bolder mb-0">{{ $metric['value'] }}</h5>
                                    <span class="text-xs text-secondary">{{ $metric['detail'] }}</span>
                                </div>
                            </div>
                            <div class="col-4 text-end">
                                <div class="icon icon-shape bg-gradient-info shadow text-center border-radius-md">
                                    <i class="fas {{ $metric['icon'] }} text-lg opacity-10" aria-hidden="true"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="row mt-4">
        <div class="col-lg-7 mb-lg-0 mb-4">
            <div class="card">
                <div class="card-header pb-0">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h6>Reservation Calendar</h6>
                            <p class="text-sm mb-0">Availability colors follow E2: available, booked, pending payment, maintenance.</p>
                        </div>
                        <a href="#" class="btn btn-sm bg-gradient-info mb-0">New reservation</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @foreach ($calendarSlots as $slot)
                            <div class="col-md-6">
                                <div class="pbj-slot pbj-slot-{{ $slot['status'] }}">
                                    <div class="d-flex justify-content-between">
                                        <span>{{ $slot['time'] }}</span>
                                        <span>{{ $slot['court'] }}</span>
                                    </div>
                                    <div class="mt-2">{{ $slot['label'] }}</div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card h-100">
                <div class="card-header pb-0">
                    <h6>Objective Work Queue</h6>
                    <p class="text-sm">Screens are organized around the approved system objectives.</p>
                </div>
                <div class="card-body p-3">
                    <div class="timeline timeline-one-side">
                        @foreach ($workQueue as $item)
                            <div class="timeline-block mb-3">
                                <span class="timeline-step">
                                    <i class="fas fa-check text-info text-gradient"></i>
                                </span>
                                <div class="timeline-content">
                                    <h6 class="text-dark text-sm font-weight-bold mb-0">{{ $item['title'] }}</h6>
                                    <p class="text-secondary font-weight-bold text-xs mt-1 mb-0">{{ $item['objective'] }}</p>
                                    <p class="text-sm mt-3 mb-2">{{ $item['description'] }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header pb-0">
                    <h6>Build Roadmap</h6>
                    <p class="text-sm mb-0">The database is ready; this UI shell maps the next modules to the objectives document.</p>
                </div>
                <div class="card-body px-0 pt-0 pb-2">
                    <div class="table-responsive p-0">
                        <table class="table align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Module</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Objectives</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Primary Roles</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ([
                                    ['module' => 'User and RBAC', 'objectives' => 'A1-A7, P1', 'roles' => 'All roles', 'status' => 'Started'],
                                    ['module' => 'Court calendar', 'objectives' => 'B1-B7, E1-E10', 'roles' => 'Admin, Staff, End User', 'status' => 'Planned'],
                                    ['module' => 'Manual GCash', 'objectives' => 'F1-F12, G1-G7', 'roles' => 'Admin, Staff, End User', 'status' => 'Planned'],
                                    ['module' => 'Walk-in and check-in/out', 'objectives' => 'H1-H7, I1-I9', 'roles' => 'Staff', 'status' => 'Planned'],
                                    ['module' => 'Reports and audit', 'objectives' => 'L9-L11, O1-O10, P2', 'roles' => 'Super Admin, Admin', 'status' => 'Planned'],
                                ] as $row)
                                    <tr>
                                        <td><div class="d-flex px-3 py-1"><h6 class="mb-0 text-sm">{{ $row['module'] }}</h6></div></td>
                                        <td><p class="text-sm font-weight-bold mb-0">{{ $row['objectives'] }}</p></td>
                                        <td><p class="text-sm mb-0">{{ $row['roles'] }}</p></td>
                                        <td><span class="badge badge-sm bg-gradient-{{ $row['status'] === 'Started' ? 'success' : 'secondary' }}">{{ $row['status'] }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
