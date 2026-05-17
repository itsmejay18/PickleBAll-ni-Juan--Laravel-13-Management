@php
    /** @var array<int, array<string, mixed>> $equipment */
    /** @var iterable<object> $locationOptions */
@endphp

<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header pb-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h6 class="mb-0">Equipment Inventory</h6>
                    <p class="text-sm mb-0">Add stock, update rental price or deposit, adjust counts after spot checks, or remove an item entirely.</p>
                </div>
                <button type="button" class="btn bg-gradient-primary mb-0"
                    data-bs-toggle="modal" data-bs-target="#equipmentModal"
                    onclick="pbjOpenEquipmentModal()">
                    <i class="fas fa-plus me-2"></i>Add stock
                </button>
            </div>
            <div class="card-body px-0 pb-2">
                <div class="table-responsive">
                    <table class="table align-items-center mb-0">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder">Item</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder">Location</th>
                                <th class="text-end text-uppercase text-secondary text-xxs font-weight-bolder">Rate</th>
                                <th class="text-end text-uppercase text-secondary text-xxs font-weight-bolder">Deposit</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder">Stock</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder">Reorder</th>
                                <th class="text-end text-uppercase text-secondary text-xxs font-weight-bolder pe-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($equipment as $item)
                                <tr>
                                    <td>
                                        <div class="d-flex px-2 py-1">
                                            <div class="avatar avatar-sm bg-gradient-info me-3 d-flex align-items-center justify-content-center">
                                                <i class="fas fa-box-open text-white text-sm"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0 text-sm">{{ $item['name'] }}</h6>
                                                @if ($item['description'])
                                                    <p class="text-xs text-secondary mb-0">{{ \Illuminate\Support\Str::limit($item['description'], 60) }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-sm">{{ $item['location_name'] }}</td>
                                    <td class="text-end text-sm font-weight-bold">PHP {{ number_format($item['rental_price_per_unit'], 2) }}</td>
                                    <td class="text-end text-sm">PHP {{ number_format($item['deposit_amount'], 2) }}</td>
                                    <td class="text-center text-sm">
                                        <span class="badge badge-sm bg-gradient-{{ $item['available_quantity'] <= $item['reorder_point'] ? 'warning' : 'success' }}">
                                            {{ $item['available_quantity'] }}/{{ $item['total_quantity'] }} avail
                                        </span>
                                        <p class="text-xs text-secondary mb-0 mt-1">{{ $item['reserved_quantity'] }} reserved</p>
                                    </td>
                                    <td class="text-center text-sm">{{ $item['reorder_point'] }}</td>
                                    <td class="text-end pe-3">
                                        <button type="button" class="btn btn-link text-info p-1 mb-0"
                                            data-bs-toggle="modal" data-bs-target="#equipmentModal"
                                            onclick='pbjOpenEquipmentModal(@json($item))'>
                                            <i class="fas fa-pen me-1"></i>Edit
                                        </button>
                                        <form method="POST" action="{{ route('equipment.destroy', $item['inventory_id']) }}" class="d-inline"
                                            onsubmit="return confirm('Remove this stock entry? Reservations holding units must be cleared first.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-link text-danger p-1 mb-0">
                                                <i class="fas fa-trash me-1"></i>Remove
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-sm text-secondary py-4">No equipment yet. Click <strong>Add stock</strong> to load paddles, balls, and accessories.</td>
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
    <div class="modal fade" id="equipmentModal" tabindex="-1" aria-labelledby="equipmentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <form id="equipmentForm" method="POST">
                    @csrf
                    <input type="hidden" name="_method" id="equipmentMethod" value="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="equipmentModalLabel">Add equipment stock</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row" id="equipmentFields">
                            <div class="col-md-6 mb-3" id="equipment_location_wrapper">
                                <label class="form-label text-xs">Location</label>
                                <select name="location_id" id="equipment_location_id" class="form-control" required>
                                    @foreach ($locationOptions as $loc)
                                        <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6 mb-3" id="equipment_name_wrapper">
                                <label class="form-label text-xs">Item name</label>
                                <input type="text" name="name" id="equipment_name" class="form-control" required maxlength="100" placeholder="Pickleball Paddle">
                            </div>

                            <div class="col-md-3 mb-3">
                                <label class="form-label text-xs">Rental price (PHP)</label>
                                <input type="number" name="rental_price_per_unit" id="equipment_price" class="form-control" required min="0" step="0.01" value="100">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label text-xs">Deposit (PHP)</label>
                                <input type="number" name="deposit_amount" id="equipment_deposit" class="form-control" min="0" step="0.01" value="0">
                            </div>
                            <div class="col-md-3 mb-3" id="equipment_quantity_wrapper">
                                <label class="form-label text-xs">Quantity to add</label>
                                <input type="number" name="quantity" id="equipment_quantity" class="form-control" min="1" value="1">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label text-xs">Low-stock threshold</label>
                                <input type="number" name="reorder_point" id="equipment_reorder_point" class="form-control" required min="0" value="5">
                            </div>

                            {{-- Edit-only fields --}}
                            <div class="col-md-3 mb-3 equipment-edit-only" style="display:none;">
                                <label class="form-label text-xs">Available now</label>
                                <input type="number" name="available_quantity" id="equipment_available" class="form-control" min="0">
                            </div>
                            <div class="col-md-3 mb-3 equipment-edit-only" style="display:none;">
                                <label class="form-label text-xs">Damaged</label>
                                <input type="number" name="damaged_quantity" id="equipment_damaged" class="form-control" min="0">
                            </div>
                            <div class="col-md-3 mb-3 equipment-edit-only" style="display:none;">
                                <label class="form-label text-xs">Lost</label>
                                <input type="number" name="lost_quantity" id="equipment_lost" class="form-control" min="0">
                            </div>
                            <div class="col-md-3 mb-3 equipment-edit-only" style="display:none;">
                                <div class="form-check form-switch mt-4">
                                    <input class="form-check-input" type="checkbox" id="equipment_rentable" name="is_available_for_rent" value="1" checked>
                                    <label class="form-check-label text-sm" for="equipment_rentable">Available for rent</label>
                                </div>
                            </div>

                            <div class="col-12 mb-3 equipment-add-only">
                                <label class="form-label text-xs">Description</label>
                                <input type="text" name="description" id="equipment_description" class="form-control" maxlength="500" placeholder="Optional item notes">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary mb-0" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn bg-gradient-primary mb-0" id="equipmentSubmit">Save stock</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function pbjOpenEquipmentModal(item) {
            const form = document.getElementById('equipmentForm');
            const method = document.getElementById('equipmentMethod');
            const submit = document.getElementById('equipmentSubmit');
            const title = document.getElementById('equipmentModalLabel');
            const editOnly = document.querySelectorAll('.equipment-edit-only');
            const addOnly = document.querySelectorAll('.equipment-add-only');
            const locationWrapper = document.getElementById('equipment_location_wrapper');
            const nameWrapper = document.getElementById('equipment_name_wrapper');
            const qtyWrapper = document.getElementById('equipment_quantity_wrapper');

            // reset
            ['equipment_name','equipment_description'].forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
            document.getElementById('equipment_price').value = '100';
            document.getElementById('equipment_deposit').value = '0';
            document.getElementById('equipment_quantity').value = '1';
            document.getElementById('equipment_reorder_point').value = '5';

            if (item && item.inventory_id) {
                form.action = "{{ url('/equipment') }}/" + item.inventory_id;
                method.value = 'PUT';
                title.textContent = 'Edit ' + item.name;
                submit.textContent = 'Save changes';
                editOnly.forEach(el => el.style.display = '');
                addOnly.forEach(el => el.style.display = 'none');
                locationWrapper.style.display = 'none';
                nameWrapper.style.display = 'none';
                qtyWrapper.style.display = 'none';

                document.getElementById('equipment_price').value = item.rental_price_per_unit;
                document.getElementById('equipment_deposit').value = item.deposit_amount;
                document.getElementById('equipment_reorder_point').value = item.reorder_point;
                document.getElementById('equipment_available').value = item.available_quantity;
                document.getElementById('equipment_damaged').value = item.damaged_quantity;
                document.getElementById('equipment_lost').value = item.lost_quantity;
                document.getElementById('equipment_rentable').checked = !!item.is_available_for_rent;
            } else {
                form.action = "{{ route('equipment.store') }}";
                method.value = 'POST';
                title.textContent = 'Add equipment stock';
                submit.textContent = 'Save stock';
                editOnly.forEach(el => el.style.display = 'none');
                addOnly.forEach(el => el.style.display = '');
                locationWrapper.style.display = '';
                nameWrapper.style.display = '';
                qtyWrapper.style.display = '';
            }
        }
    </script>
@endpush
