@php
    /** @var array<int, array<string, mixed>> $users */
    /** @var iterable<object> $locationOptions */
    /** @var bool $isSuperAdmin */
    $authUserId = auth()->id();
    $roleLabels = [
        'super_admin' => 'Super Admin',
        'admin' => 'Admin',
        'location_manager' => 'Location Manager',
        'staff' => 'Staff',
        'end_user' => 'End User',
    ];
@endphp

<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header pb-0 d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h6 class="mb-0">User Management</h6>
                    <p class="text-sm mb-0">Create staff, admins, and end-user accounts. Reset passwords, lock or archive accounts as needed.</p>
                </div>
                <button type="button" class="btn bg-gradient-primary mb-0"
                    data-bs-toggle="modal" data-bs-target="#userModal"
                    onclick="pbjOpenUserModal()">
                    <i class="fas fa-plus me-2"></i>Add user
                </button>
            </div>
            <div class="card-body px-0 pb-2">
                <div class="table-responsive">
                    <table class="table align-items-center mb-0">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder">User</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder">Role</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder">Branch</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder">Last login</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder">Status</th>
                                <th class="text-end text-uppercase text-secondary text-xxs font-weight-bolder pe-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($users as $u)
                                <tr>
                                    <td>
                                        <div class="d-flex px-2 py-1">
                                            <div class="avatar avatar-sm bg-gradient-info me-3 d-flex align-items-center justify-content-center">
                                                <i class="fas fa-user text-white text-sm"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0 text-sm">{{ $u['display_name'] }}</h6>
                                                <p class="text-xs text-secondary mb-0">{{ $u['email'] }}</p>
                                                <p class="text-xs text-secondary mb-0">{{ $u['mobile_number'] }}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-sm">
                                        <span class="badge badge-sm bg-gradient-{{ in_array($u['role'], ['super_admin','admin']) ? 'dark' : (in_array($u['role'], ['location_manager','staff']) ? 'info' : 'secondary') }}">
                                            {{ $roleLabels[$u['role']] ?? $u['role'] }}
                                        </span>
                                        @if ($u['role'] === 'staff' || $u['role'] === 'location_manager')
                                            <p class="text-xs text-secondary mb-0 mt-1">{{ $u['employee_id'] ?: '—' }}</p>
                                        @endif
                                    </td>
                                    <td class="text-sm">{{ $u['location_name'] ?: '—' }}</td>
                                    <td class="text-center text-xs text-secondary">
                                        {{ $u['last_login_at'] ? \Illuminate\Support\Carbon::parse($u['last_login_at'])->diffForHumans() : 'Never' }}
                                    </td>
                                    <td class="text-center">
                                        @if ($u['is_locked'])
                                            <span class="badge badge-sm bg-gradient-warning">Locked</span>
                                        @elseif ($u['is_active'])
                                            <span class="badge badge-sm bg-gradient-success">Active</span>
                                        @else
                                            <span class="badge badge-sm bg-gradient-secondary">Disabled</span>
                                        @endif
                                    </td>
                                    <td class="text-end pe-3">
                                        <button type="button" class="btn btn-link text-info p-1 mb-0"
                                            data-bs-toggle="modal" data-bs-target="#userModal"
                                            onclick='pbjOpenUserModal(@json($u))'>
                                            <i class="fas fa-pen me-1"></i>Edit
                                        </button>

                                        @if ($u['id'] !== $authUserId)
                                            <form method="POST" action="{{ route('users.toggle-active', $u['id']) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-link text-{{ $u['is_active'] ? 'secondary' : 'success' }} p-1 mb-0">
                                                    <i class="fas fa-{{ $u['is_active'] ? 'user-slash' : 'user-check' }} me-1"></i>{{ $u['is_active'] ? 'Disable' : 'Enable' }}
                                                </button>
                                            </form>

                                            <form method="POST" action="{{ route('users.password-reset', $u['id']) }}" class="d-inline"
                                                onsubmit="return confirm('Send a password reset link to {{ $u['email'] }}?');">
                                                @csrf
                                                <button type="submit" class="btn btn-link text-warning p-1 mb-0">
                                                    <i class="fas fa-key me-1"></i>Reset
                                                </button>
                                            </form>

                                            <form method="POST" action="{{ route('users.destroy', $u['id']) }}" class="d-inline"
                                                onsubmit="return confirm('Archive {{ $u['email'] }}? They will lose access immediately.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-link text-danger p-1 mb-0">
                                                    <i class="fas fa-archive me-1"></i>Archive
                                                </button>
                                            </form>
                                        @else
                                            <span class="text-xs text-secondary">(you)</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-sm text-secondary py-4">No users yet. Click <strong>Add user</strong> to create one.</td>
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
    <div class="modal fade" id="userModal" tabindex="-1" aria-labelledby="userModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <form id="userForm" method="POST">
                    @csrf
                    <input type="hidden" name="_method" id="userMethod" value="POST">
                    <div class="modal-header">
                        <h5 class="modal-title" id="userModalLabel">Add user</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label text-xs">Role</label>
                                <select name="role" id="user_role" class="form-control" required onchange="pbjUserRoleChanged()">
                                    <option value="end_user">End User (customer)</option>
                                    <option value="staff">Staff</option>
                                    <option value="location_manager">Location Manager</option>
                                    <option value="admin">Admin</option>
                                    @if ($isSuperAdmin)
                                        <option value="super_admin">Super Admin</option>
                                    @endif
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label text-xs">First name</label>
                                <input type="text" name="first_name" id="user_first_name" class="form-control" required maxlength="100">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label text-xs">Last name</label>
                                <input type="text" name="last_name" id="user_last_name" class="form-control" required maxlength="100">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label text-xs">Email</label>
                                <input type="email" name="email" id="user_email" class="form-control" required maxlength="255">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label text-xs">Mobile number</label>
                                <input type="text" name="mobile_number" id="user_mobile_number" class="form-control" required maxlength="20" placeholder="09XXXXXXXXX">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label class="form-label text-xs">Middle name</label>
                                <input type="text" name="middle_name" id="user_middle_name" class="form-control" maxlength="100">
                            </div>

                            <div class="col-md-6 mb-3">
                                <label class="form-label text-xs">Password <span class="text-secondary text-xxs" id="user_password_hint">(min 8 characters)</span></label>
                                <input type="password" name="password" id="user_password" class="form-control" minlength="8" autocomplete="new-password">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label text-xs">Confirm password</label>
                                <input type="password" name="password_confirmation" id="user_password_confirmation" class="form-control" minlength="8" autocomplete="new-password">
                            </div>

                            {{-- Staff/manager fields --}}
                            <div class="col-12 pbj-staff-only" style="display:none;">
                                <hr class="horizontal dark my-2">
                                <p class="text-xs text-uppercase text-secondary mb-2">Staff settings</p>
                            </div>
                            <div class="col-md-3 mb-3 pbj-staff-only" style="display:none;">
                                <label class="form-label text-xs">Employee ID</label>
                                <input type="text" name="employee_id" id="user_employee_id" class="form-control" maxlength="50" placeholder="auto-generated if blank">
                            </div>
                            <div class="col-md-3 mb-3 pbj-staff-only" style="display:none;">
                                <label class="form-label text-xs">Position</label>
                                <input type="text" name="position" id="user_position" class="form-control" maxlength="100">
                            </div>
                            <div class="col-md-3 mb-3 pbj-staff-only" style="display:none;">
                                <label class="form-label text-xs">Hire date</label>
                                <input type="date" name="hire_date" id="user_hire_date" class="form-control">
                            </div>
                            <div class="col-md-3 mb-3 pbj-staff-only" style="display:none;">
                                <label class="form-label text-xs">Branch</label>
                                <select name="assigned_location_id" id="user_assigned_location_id" class="form-control">
                                    <option value="">— None —</option>
                                    @foreach ($locationOptions as $loc)
                                        <option value="{{ $loc->id }}">{{ $loc->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 mb-3 pbj-staff-only" style="display:none;">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="user_can_confirm_payments" name="can_confirm_payments" value="1">
                                    <label class="form-check-label text-sm" for="user_can_confirm_payments">Can confirm GCash payments</label>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3 pbj-staff-only" style="display:none;">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="user_can_process_refunds" name="can_process_refunds" value="1">
                                    <label class="form-check-label text-sm" for="user_can_process_refunds">Can process refunds</label>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3 pbj-staff-only" style="display:none;">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="user_can_manage_inventory" name="can_manage_inventory" value="1">
                                    <label class="form-check-label text-sm" for="user_can_manage_inventory">Can manage inventory</label>
                                </div>
                            </div>

                            {{-- Admin fields --}}
                            <div class="col-12 pbj-admin-only" style="display:none;">
                                <hr class="horizontal dark my-2">
                                <p class="text-xs text-uppercase text-secondary mb-2">Admin settings</p>
                            </div>
                            <div class="col-md-4 mb-3 pbj-admin-only" style="display:none;">
                                <label class="form-label text-xs">Admin level</label>
                                <select name="admin_level" id="user_admin_level" class="form-control">
                                    <option value="restricted">Restricted</option>
                                    <option value="full">Full</option>
                                    <option value="super">Super</option>
                                </select>
                            </div>

                            <div class="col-12">
                                <hr class="horizontal dark my-2">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="user_is_active" name="is_active" value="1" checked>
                                    <label class="form-check-label text-sm" for="user_is_active">Account active</label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary mb-0" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn bg-gradient-primary mb-0" id="userSubmit">Save user</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function pbjUserRoleChanged() {
            const role = document.getElementById('user_role').value;
            const showStaff = role === 'staff' || role === 'location_manager';
            const showAdmin = role === 'admin' || role === 'super_admin';
            document.querySelectorAll('.pbj-staff-only').forEach(el => el.style.display = showStaff ? '' : 'none');
            document.querySelectorAll('.pbj-admin-only').forEach(el => el.style.display = showAdmin ? '' : 'none');
        }

        function pbjOpenUserModal(user) {
            const form = document.getElementById('userForm');
            const method = document.getElementById('userMethod');
            const submit = document.getElementById('userSubmit');
            const title = document.getElementById('userModalLabel');
            const passwordHint = document.getElementById('user_password_hint');

            // Reset
            ['user_first_name','user_last_name','user_middle_name','user_email','user_mobile_number',
             'user_password','user_password_confirmation','user_employee_id','user_position',
             'user_hire_date'].forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
            document.getElementById('user_role').value = 'end_user';
            document.getElementById('user_is_active').checked = true;
            document.getElementById('user_assigned_location_id').value = '';
            ['user_can_confirm_payments','user_can_process_refunds','user_can_manage_inventory'].forEach(id => {
                document.getElementById(id).checked = false;
            });
            document.getElementById('user_admin_level').value = 'restricted';

            if (user && user.id) {
                form.action = "{{ url('/users') }}/" + user.id;
                method.value = 'PUT';
                title.textContent = 'Edit user: ' + (user.display_name || user.email);
                submit.textContent = 'Save changes';
                passwordHint.textContent = '(leave blank to keep current password)';
                document.getElementById('user_password').required = false;
                document.getElementById('user_password_confirmation').required = false;

                document.getElementById('user_role').value = user.role || 'end_user';
                document.getElementById('user_first_name').value = user.first_name || '';
                document.getElementById('user_last_name').value = user.last_name || '';
                document.getElementById('user_email').value = user.email || '';
                document.getElementById('user_mobile_number').value = user.mobile_number || '';
                document.getElementById('user_employee_id').value = user.employee_id || '';
                document.getElementById('user_position').value = user.position || '';
                document.getElementById('user_assigned_location_id').value = user.assigned_location_id || '';
                document.getElementById('user_can_confirm_payments').checked = !!user.can_confirm_payments;
                document.getElementById('user_can_process_refunds').checked = !!user.can_process_refunds;
                document.getElementById('user_can_manage_inventory').checked = !!user.can_manage_inventory;
                document.getElementById('user_admin_level').value = user.admin_level || 'restricted';
                document.getElementById('user_is_active').checked = !!user.is_active;
            } else {
                form.action = "{{ route('users.store') }}";
                method.value = 'POST';
                title.textContent = 'Add user';
                submit.textContent = 'Save user';
                passwordHint.textContent = '(min 8 characters)';
                document.getElementById('user_password').required = true;
                document.getElementById('user_password_confirmation').required = true;
            }

            pbjUserRoleChanged();
        }
    </script>
@endpush
