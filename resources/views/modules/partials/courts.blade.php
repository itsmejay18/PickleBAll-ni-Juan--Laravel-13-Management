@php
    /** @var array<int, array<string, mixed>> $courts */
    /** @var iterable<object> $locationOptions */
    $courtTypes = ['indoor' => 'Indoor', 'outdoor' => 'Outdoor', 'covered' => 'Covered'];
    $surfaces = ['acrylic' => 'Acrylic', 'concrete' => 'Concrete', 'asphalt' => 'Asphalt', 'grass' => 'Grass', 'clay' => 'Clay'];
@endphp

<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header pb-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h6 class="mb-0">Court Management</h6>
                    <p class="text-sm mb-0">Add a court, update its rate, hide it from booking, or archive it when retired.</p>
                </div>
                <button type="button" class="btn bg-gradient-primary mb-0"
                    data-bs-toggle="modal" data-bs-target="#courtModal"
                    onclick="pbjOpenCourtModal()">
                    <i class="fas fa-plus me-2"></i>Add court
                </button>
            </div>
            <div class="card-body px-0 pb-2">
                <div class="table-responsive">
                    <table class="table align-items-center mb-0">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder">Court</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder">Location</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder">Type</th>
                                <th class="text-end text-uppercase text-secondary text-xxs font-weight-bolder">Rate</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder">Bookings today</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder">Status</th>
                                <th class="text-end text-uppercase text-secondary text-xxs font-weight-bolder pe-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($courts as $court)
                                <tr>
                                    <td>
                                        <div class="d-flex px-2 py-1">
                                            <div class="avatar avatar-sm bg-gradient-info me-3 d-flex align-items-center justify-content-center">
                                                <i class="fas fa-table-tennis text-white text-sm"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0 text-sm">{{ $court['court_name'] ?: 'Court '.$court['court_number'] }}</h6>
                                                <p class="text-xs text-secondary mb-0">#{{ $court['court_number'] }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-sm">{{ $court['location_name'] }}</td>
                                    <td class="text-sm">{{ ucfirst($court['court_type']) }} / {{ ucfirst($court['surface_type']) }}</td>
                                    <td class="text-end text-sm font-weight-bold">PHP {{ number_format($court['base_price'], 2) }}</td>
                                    <td class="text-center text-sm">{{ $court['today_bookings'] }}</td>
                                    <td class="text-center">
                                        <span class="badge badge-sm bg-gradient-{{ $court['is_active'] ? 'success' : 'secondary' }}">
                                            {{ $court['is_active'] ? 'Active' : 'Hidden' }}
                                        </span>
                                    </td>
                                    <td class="text-end pe-3">
                                        <button type="button" class="btn btn-link text-info p-1 mb-0"
                                            data-bs-toggle="modal" data-bs-target="#courtModal"
                                            onclick='pbjOpenCourtModal(@json($court))'>
                                            <i class="fas fa-pen me-1"></i>Edit
                                        </button>
                                        <form method="POST" action="{{ route('courts.toggle', $court['id']) }}" class="d-inline">
                                            @csrf
                                            <button type="submit" class="btn btn-link text-{{ $court['is_active'] ? 'secondary' : 'success' }} p-1 mb-0">
                                                <i class="fas fa-{{ $court['is_active'] ? 'eye-slash' : 'eye' }} me-1"></i>{{ $court['is_active'] ? 'Hide' : 'Show' }}
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('courts.destroy', $court['id']) }}" class="d-inline"
                                            onsubmit="return confirm('Archive this court? You cannot archive a court with upcoming bookings.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-link text-danger p-1 mb-0">
                                                <i class="fas fa-archive me-1"></i>Archive
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-sm text-secondary py-4">No courts yet. Click <strong>Add court</strong> to create one.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@push('modals')
    <div class="modal fade" id="courtModal" tabindex="-1" aria-labelledby="courtModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form id="courtForm" method="POST">
                    @csrf
                    <input type="hidden" name="_method" id="courtMethod" value="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="courtModalLabel">Add court</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6 mb-3" id="court_location_wrapper">
                                <label class="form-label text-xs">Location</label>
                                <select name="location_id" id="court_location_id" class="form-control" required>
                                    @foreach ($locationOptions as $loc)
                                        <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label text-xs">Court number</label>
                                <input type="text" name="court_number" id="court_number" class="form-control" required maxlength="50" placeholder="A">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label text-xs">Hourly rate (PHP)</label>
                                <input type="number" name="base_price" id="court_base_price" class="form-control" required min="0" step="0.01" value="600">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-xs">Court name</label>
                                <input type="text" name="court_name" id="court_name" class="form-control" maxlength="200" placeholder="Center Court">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label text-xs">Type</label>
                                <select name="court_type" id="court_type" class="form-control" required>
                                    @foreach ($courtTypes as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label text-xs">Surface</label>
                                <select name="surface_type" id="court_surface_type" class="form-control" required>
                                    @foreach ($surfaces as $value => $label)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12 mb-2">
                                <div class="form-check form-switch" id="court_is_active_wrapper" style="display:none;">
                                    <input class="form-check-input" type="checkbox" id="court_is_active" name="is_active" value="1" checked>
                                    <label class="form-check-label text-sm" for="court_is_active">Visible for booking</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary mb-0" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn bg-gradient-primary mb-0" id="courtSubmit">Save court</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function pbjOpenCourtModal(court) {
            const form = document.getElementById('courtForm');
            const method = document.getElementById('courtMethod');
            const submit = document.getElementById('courtSubmit');
            const title = document.getElementById('courtModalLabel');
            const locationWrapper = document.getElementById('court_location_wrapper');
            const isActiveWrapper = document.getElementById('court_is_active_wrapper');

            ['court_number','court_name','court_base_price'].forEach(id => document.getElementById(id).value = '');
            document.getElementById('court_base_price').value = '600';
            document.getElementById('court_type').value = 'outdoor';
            document.getElementById('court_surface_type').value = 'acrylic';

            if (court && court.id) {
                form.action = "{{ url('/courts') }}/" + court.id;
                method.value = 'PUT';
                title.textContent = 'Edit court ' + court.court_number;
                submit.textContent = 'Save changes';
                document.getElementById('court_location_id').value = court.location_id;
                document.getElementById('court_number').value = court.court_number;
                document.getElementById('court_name').value = court.court_name || '';
                document.getElementById('court_type').value = court.court_type;
                document.getElementById('court_surface_type').value = court.surface_type;
                document.getElementById('court_base_price').value = court.base_price;
                locationWrapper.style.display = 'none';
                isActiveWrapper.style.display = 'block';
                document.getElementById('court_is_active').checked = !!court.is_active;
            } else {
                form.action = "{{ route('courts.store') }}";
                method.value = 'POST';
                title.textContent = 'Add court';
                submit.textContent = 'Save court';
                locationWrapper.style.display = 'block';
                isActiveWrapper.style.display = 'none';
            }
        }
    </script>
@endpush
