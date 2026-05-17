@php
    /** @var array<int, array<string, mixed>> $locations */
@endphp

<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header pb-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h6 class="mb-0">Branch Locations</h6>
                    <p class="text-sm mb-0">Add, edit, archive, and toggle visibility for every Pickle Ballan ni Juan branch.</p>
                </div>
                <button type="button" class="btn bg-gradient-primary mb-0"
                    data-bs-toggle="modal" data-bs-target="#locationModal"
                    onclick="pbjOpenLocationModal()">
                    <i class="fas fa-plus me-2"></i>Add location
                </button>
            </div>
            <div class="card-body px-0 pb-2">
                <div class="table-responsive">
                    <table class="table align-items-center mb-0">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder">Branch</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder">Address</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder">Courts</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder">Status</th>
                                <th class="text-end text-uppercase text-secondary text-xxs font-weight-bolder pe-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($locations as $location)
                                <tr id="location-row-{{ $location['id'] }}">
                                    <td>
                                        <div class="d-flex px-2 py-1">
                                            <div class="avatar avatar-sm bg-gradient-info me-3 d-flex align-items-center justify-content-center">
                                                <i class="fas fa-map-marker-alt text-white text-sm"></i>
                                            </div>
                                            <div class="d-flex flex-column justify-content-center">
                                                <h6 class="mb-0 text-sm">{{ $location['name'] }}</h6>
                                                <p class="text-xs text-secondary mb-0">Code {{ $location['branch_code'] }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-sm">
                                        <p class="mb-0">{{ $location['address_line1'] }}</p>
                                        <p class="text-xs text-secondary mb-0">
                                            {{ collect([$location['city'], $location['province'], $location['country']])->filter()->implode(', ') }}
                                        </p>
                                    </td>
                                    <td class="text-center text-sm font-weight-bold">{{ $location['court_count'] }}</td>
                                    <td class="text-center">
                                        <span class="badge badge-sm bg-gradient-{{ $location['is_active'] ? 'success' : 'secondary' }}">
                                            {{ $location['is_active'] ? 'Active' : 'Hidden' }}
                                        </span>
                                    </td>
                                    <td class="text-end pe-3">
                                        <button type="button" class="btn btn-link text-info p-1 mb-0"
                                            data-bs-toggle="modal" data-bs-target="#locationModal"
                                            onclick='pbjOpenLocationModal(@json($location))'>
                                            <i class="fas fa-pen me-1"></i>Edit
                                        </button>
                                        <form method="POST" action="{{ route('locations.destroy', $location['id']) }}" class="d-inline"
                                            onsubmit="return confirm('Archive this location? Bookings will not be deleted, but it will stop accepting new ones.');">
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
                                    <td colspan="5" class="text-center text-sm text-secondary py-4">No locations yet. Click <strong>Add location</strong> to create the first branch.</td>
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
    <div class="modal fade" id="locationModal" tabindex="-1" aria-labelledby="locationModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <form id="locationForm" method="POST">
                    @csrf
                    <input type="hidden" name="_method" id="locationMethod" value="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="locationModalLabel">Add location</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label class="form-label text-xs">Branch name</label>
                                <input type="text" name="name" id="loc_name" class="form-control" required maxlength="200">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label text-xs">Branch code</label>
                                <input type="text" name="branch_code" id="loc_branch_code" class="form-control" required maxlength="20" placeholder="PBJ02">
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label text-xs">Address line 1</label>
                                <input type="text" name="address_line1" id="loc_address_line1" class="form-control" required maxlength="500">
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label text-xs">Address line 2</label>
                                <input type="text" name="address_line2" id="loc_address_line2" class="form-control" maxlength="500">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label text-xs">City</label>
                                <input type="text" name="city" id="loc_city" class="form-control" required maxlength="100">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label text-xs">Province</label>
                                <input type="text" name="province" id="loc_province" class="form-control" maxlength="100">
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="form-label text-xs">Postal code</label>
                                <input type="text" name="postal_code" id="loc_postal_code" class="form-control" maxlength="20">
                            </div>
                            <div class="col-md-2 mb-3">
                                <label class="form-label text-xs">Country</label>
                                <input type="text" name="country" id="loc_country" class="form-control" maxlength="100" value="Philippines">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label text-xs">Latitude</label>
                                <input type="number" step="0.00000001" name="latitude" id="loc_latitude" class="form-control">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label text-xs">Longitude</label>
                                <input type="number" step="0.00000001" name="longitude" id="loc_longitude" class="form-control">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label text-xs">WhatsApp</label>
                                <input type="text" name="whatsapp_number" id="loc_whatsapp_number" class="form-control" maxlength="20">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label text-xs">Landline</label>
                                <input type="text" name="landline_number" id="loc_landline_number" class="form-control" maxlength="20">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-xs">Email</label>
                                <input type="email" name="email_address" id="loc_email_address" class="form-control" maxlength="255">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-xs">Timezone</label>
                                <input type="text" name="timezone" id="loc_timezone" class="form-control" value="Asia/Manila" maxlength="50">
                            </div>
                            <div class="col-12 mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="loc_is_active" name="is_active" value="1" checked>
                                    <label class="form-check-label text-sm" for="loc_is_active">Active and accepting bookings</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary mb-0" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn bg-gradient-primary mb-0" id="locationSubmit">Save location</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function pbjOpenLocationModal(location) {
            const form = document.getElementById('locationForm');
            const method = document.getElementById('locationMethod');
            const submit = document.getElementById('locationSubmit');
            const title = document.getElementById('locationModalLabel');
            const fields = ['name','branch_code','address_line1','address_line2','city','province','postal_code','country','latitude','longitude','whatsapp_number','landline_number','email_address','timezone'];

            fields.forEach(f => {
                const el = document.getElementById('loc_' + f);
                if (el) el.value = '';
            });
            document.getElementById('loc_country').value = 'Philippines';
            document.getElementById('loc_timezone').value = 'Asia/Manila';
            document.getElementById('loc_is_active').checked = true;

            if (location && location.id) {
                form.action = "{{ url('/locations') }}/" + location.id;
                method.value = 'PUT';
                title.textContent = 'Edit location: ' + location.name;
                submit.textContent = 'Save changes';
                fields.forEach(f => {
                    const el = document.getElementById('loc_' + f);
                    if (el && location[f] !== null && location[f] !== undefined) el.value = location[f];
                });
                document.getElementById('loc_is_active').checked = !!location.is_active;
            } else {
                form.action = "{{ route('locations.store') }}";
                method.value = 'POST';
                title.textContent = 'Add location';
                submit.textContent = 'Save location';
            }
        }
    </script>
@endpush
