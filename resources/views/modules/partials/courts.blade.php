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
                                        <button type="button" class="btn btn-link text-warning p-1 mb-0"
                                            onclick='pbjOpenRatesModal(@json($court))'>
                                            <i class="fas fa-tags me-1"></i>Rates
                                        </button>
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
                            <div class="col-md-3 mb-3" id="court_base_price_wrapper">
                                <label class="form-label text-xs">Hourly rate (PHP)</label>
                                <input type="number" name="base_price" id="court_base_price" class="form-control" required min="0" step="0.01" value="600">
                            </div>
                            <input type="hidden" name="base_price" id="court_base_price_hidden" disabled>
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
                
                document.getElementById('court_base_price_wrapper').style.display = 'none';
                document.getElementById('court_base_price').disabled = true;
                document.getElementById('court_base_price_hidden').disabled = false;
                document.getElementById('court_base_price_hidden').value = court.base_price;
                
                locationWrapper.style.display = 'none';
                isActiveWrapper.style.display = 'block';
                document.getElementById('court_is_active').checked = !!court.is_active;
            } else {
                form.action = "{{ route('courts.store') }}";
                method.value = 'POST';
                title.textContent = 'Add court';
                submit.textContent = 'Save court';
                
                document.getElementById('court_base_price_wrapper').style.display = 'block';
                document.getElementById('court_base_price').disabled = false;
                document.getElementById('court_base_price_hidden').disabled = true;
                
                locationWrapper.style.display = 'block';
                isActiveWrapper.style.display = 'none';
            }
        }
    </script>

    <div class="modal fade" id="courtRatesModal" tabindex="-1" aria-labelledby="courtRatesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="courtRatesModalLabel">Manage Rate Windows</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <h6 class="text-xs text-uppercase text-secondary font-weight-bolder mb-3" id="ratesCourtName">Court</h6>
                    
                    <div class="border rounded p-3 bg-light text-start mb-4">
                        <h6 class="text-sm font-weight-bold mb-3"><i class="fas fa-edit me-1 text-primary"></i>Edit Court Base Rate</h6>
                        <form id="editCourtBasePriceForm" method="POST">
                            @csrf
                            @method('PUT')
                            <input type="hidden" name="court_number" id="ebp_court_number">
                            <input type="hidden" name="court_name" id="ebp_court_name">
                            <input type="hidden" name="court_type" id="ebp_court_type">
                            <input type="hidden" name="surface_type" id="ebp_surface_type">
                            <input type="hidden" name="is_active" id="ebp_is_active">
                            <div class="row align-items-end">
                                <div class="col-md-8">
                                    <label class="form-label text-xs">Base Hourly Rate (PHP)</label>
                                    <input type="number" name="base_price" id="ebp_base_price" class="form-control" required min="0" step="0.01">
                                </div>
                                <div class="col-md-4 mt-2">
                                    <button type="submit" class="btn bg-gradient-primary mb-0 w-100"><i class="fas fa-save me-1"></i>Update Base Rate</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Table showing existing rates -->
                    <div class="table-responsive mb-4">
                        <table class="table align-items-center mb-0" id="ratesTable">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder px-2">Day of Week</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder px-2">Time Window</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder px-2">Base Price</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder px-2">Effective Until</th>
                                    <th class="text-end text-uppercase text-secondary text-xxs font-weight-bolder px-2 pe-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="ratesTableBody">
                                <!-- Loaded dynamically via fetch -->
                            </tbody>
                        </table>
                    </div>

                    <!-- Add Rate Window Form -->
                    <div class="border rounded p-3 bg-light text-start">
                        <h6 class="text-sm font-weight-bold mb-3" id="rateFormTitle"><i class="fas fa-plus me-1 text-primary"></i>Add Pricing Rule Window</h6>
                        <form id="addRateForm" method="POST">
                            @csrf
                            <input type="hidden" name="_method" id="rateFormMethod" value="POST">
                            <input type="hidden" name="court_id" id="ratesCourtId">
                            <div class="row">
                                <div class="col-md-4 mb-2">
                                    <label class="form-label text-xs">Day of Week (Optional)</label>
                                    <select name="day_of_week" id="rates_day_of_week" class="form-control">
                                        <option value="">Any Day</option>
                                        <option value="0">Sunday</option>
                                        <option value="1">Monday</option>
                                        <option value="2">Tuesday</option>
                                        <option value="3">Wednesday</option>
                                        <option value="4">Thursday</option>
                                        <option value="5">Friday</option>
                                        <option value="6">Saturday</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label text-xs">Start Time</label>
                                    <input type="time" name="start_time" id="rates_start_time" class="form-control" required>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label class="form-label text-xs">End Time</label>
                                    <input type="time" name="end_time" id="rates_end_time" class="form-control" required>
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label text-xs">Base Price (PHP)</label>
                                    <input type="number" name="base_price" id="rates_base_price" class="form-control" required min="0" step="0.01" value="600">
                                </div>
                                <div class="col-md-6 mb-2">
                                    <label class="form-label text-xs">Effective To (Optional)</label>
                                    <input type="date" name="effective_to" id="rates_effective_to" class="form-control">
                                </div>
                                <div class="col-12 text-end mt-3" id="rateFormActions">
                                    <button type="submit" class="btn bg-gradient-primary mb-0" id="rateFormSubmitBtn"><i class="fas fa-plus me-1"></i>Add Rule</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pricing rules controller helper scripts -->
    <script>
        let ratesModalInstance = null;
        function pbjOpenRatesModal(court) {
            if (!court) return;
            document.getElementById('ratesCourtId').value = court.id;
            document.getElementById('ratesCourtName').textContent = (court.court_name || 'Court ' + court.court_number) + ' Rates';
            
            pbjResetRateForm(court.id);
            
            // Populate edit base price form
            document.getElementById('editCourtBasePriceForm').action = `/courts/${court.id}`;
            document.getElementById('ebp_court_number').value = court.court_number;
            document.getElementById('ebp_court_name').value = court.court_name || '';
            document.getElementById('ebp_court_type').value = court.court_type;
            document.getElementById('ebp_surface_type').value = court.surface_type;
            document.getElementById('ebp_is_active').value = court.is_active ? '1' : '0';
            document.getElementById('ebp_base_price').value = court.base_price;

            fetchRates(court.id);
            
            if (!ratesModalInstance) {
                ratesModalInstance = new bootstrap.Modal(document.getElementById('courtRatesModal'));
            }
            ratesModalInstance.show();
        }
        
        function pbjFormatTime(timeStr) {
            if (!timeStr) return '';
            const parts = timeStr.split(':');
            let h = parseInt(parts[0], 10);
            const m = parseInt(parts[1] || '0', 10);
            const ampm = h >= 12 ? 'PM' : 'AM';
            h = h % 12;
            h = h ? h : 12;
            const minStr = String(m).padStart(2, '0');
            return `${h}:${minStr} ${ampm}`;
        }

        function fetchRates(courtId) {
            const tbody = document.getElementById('ratesTableBody');
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-secondary py-3"><i class="fas fa-spinner fa-spin me-2"></i>Loading rules...</td></tr>';
            
            fetch(`/courts/${courtId}/rates`)
                .then(res => res.json())
                .then(data => {
                    tbody.innerHTML = '';
                    const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
                    if (data.length === 0) {
                        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-secondary py-3">No custom rates for this court. Default base price will apply.</td></tr>';
                        return;
                    }
                    data.forEach(rate => {
                        const dayText = rate.day_of_week !== null ? days[rate.day_of_week] : 'Any Day';
                        const timeText = (rate.start_time && rate.end_time) ? `${pbjFormatTime(rate.start_time)} - ${pbjFormatTime(rate.end_time)}` : 'All Day';
                        const basePrice = parseFloat(rate.base_price).toFixed(2);
                        const untilText = rate.effective_to || 'No End Date';
                        
                        const actionsHtml = rate.start_time !== null ? `
                            <button type="button" class="btn btn-link text-primary p-1 mb-0 me-2" onclick='pbjEditRate(${JSON.stringify(rate)})'>
                                <i class="fas fa-edit me-1"></i>Edit
                            </button>
                            <button type="button" class="btn btn-link text-danger p-1 mb-0" onclick="pbjDeleteRate(${rate.id}, ${courtId})">
                                <i class="fas fa-trash me-1"></i>Delete
                            </button>
                        ` : '<span class="text-xs text-muted">Baseline Rate</span>';
                        
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                            <td class="text-sm px-2">${dayText}</td>
                            <td class="text-sm font-weight-bold px-2">${timeText}</td>
                            <td class="text-sm px-2">PHP ${basePrice}</td>
                            <td class="text-xs text-secondary px-2">${untilText}</td>
                            <td class="text-end px-2 pe-3">${actionsHtml}</td>
                        `;
                        tbody.appendChild(tr);
                    });
                })
                .catch(err => {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger py-3">Error loading rate windows.</td></tr>';
                });
        }
        
        function pbjDeleteRate(rateId, courtId) {
            if (!confirm('Are you sure you want to delete this pricing rule window?')) return;
            
            fetch(`/courts/rates/${rateId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                }
            })
            .then(res => {
                if (res.ok) {
                    pbjResetRateForm(courtId);
                    fetchRates(courtId);
                } else {
                    alert('Failed to delete pricing rule window.');
                }
            })
            .catch(err => {
                alert('An error occurred while deleting.');
            });
        }

        function pbjEditRate(rate) {
            if (!rate) return;
            
            // Set form to Edit mode
            document.getElementById('rateFormTitle').innerHTML = '<i class="fas fa-edit me-1 text-primary"></i>Edit Pricing Rule Window';
            document.getElementById('addRateForm').action = `/courts/rates/${rate.id}`;
            document.getElementById('rateFormMethod').value = 'PUT';
            document.getElementById('rateFormSubmitBtn').innerHTML = '<i class="fas fa-save me-1"></i>Save Rule';
            
            // Populate inputs
            document.getElementById('rates_day_of_week').value = rate.day_of_week !== null ? rate.day_of_week : '';
            document.getElementById('rates_start_time').value = rate.start_time ? rate.start_time.substring(0, 5) : '';
            document.getElementById('rates_end_time').value = rate.end_time ? rate.end_time.substring(0, 5) : '';
            document.getElementById('rates_base_price').value = rate.base_price;
            document.getElementById('rates_effective_to').value = rate.effective_to || '';
            
            // Add Cancel button if not already present
            if (!document.getElementById('rateFormCancelBtn')) {
                const cancelBtn = document.createElement('button');
                cancelBtn.type = 'button';
                cancelBtn.className = 'btn btn-outline-secondary mb-0 ms-2';
                cancelBtn.id = 'rateFormCancelBtn';
                cancelBtn.innerHTML = 'Cancel';
                cancelBtn.onclick = function() {
                    pbjResetRateForm(rate.court_id);
                };
                document.getElementById('rateFormActions').appendChild(cancelBtn);
            }
        }
        
        function pbjResetRateForm(courtId) {
            // Reset form to Add mode
            document.getElementById('rateFormTitle').innerHTML = '<i class="fas fa-plus me-1 text-primary"></i>Add Pricing Rule Window';
            document.getElementById('addRateForm').action = `/courts/${courtId}/rates`;
            document.getElementById('rateFormMethod').value = 'POST';
            document.getElementById('rateFormSubmitBtn').innerHTML = '<i class="fas fa-plus me-1"></i>Add Rule';
            
            // Reset inputs
            document.getElementById('addRateForm').reset();
            document.getElementById('ratesCourtId').value = courtId;
            
            // Remove Cancel button if present
            const cancelBtn = document.getElementById('rateFormCancelBtn');
            if (cancelBtn) {
                cancelBtn.remove();
            }
        }

        document.getElementById('addRateForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const form = this;
            const courtId = document.getElementById('ratesCourtId').value;
            const data = new FormData(form);
            
            fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Accept': 'application/json'
                },
                body: data
            })
            .then(res => res.json())
            .then(resData => {
                if (resData.success) {
                    pbjResetRateForm(courtId);
                    fetchRates(courtId);
                } else {
                    alert(resData.message || 'Failed to add/save pricing rule window.');
                }
            })
            .catch(err => {
                alert('An error occurred while saving pricing rule.');
            });
        });
    </script>
@endpush
