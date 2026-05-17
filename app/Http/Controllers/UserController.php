<?php

namespace App\Http\Controllers;

use App\Models\AdminProfile;
use App\Models\EndUserProfile;
use App\Models\StaffProfile;
use App\Models\User;
use App\Services\AuditService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly NotificationService $notifications,
    ) {}

    /**
     * Create staff / admin / manager / super_admin / end_user accounts.
     * Objective L5 / A3.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorizeManage($request);

        $validated = $request->validate($this->rules($request, null));

        $role = $validated['role'];
        $this->guardRoleAssignment($request, $role);

        $user = DB::transaction(function () use ($request, $validated, $role) {
            $user = User::query()->create([
                'email' => $validated['email'],
                'mobile_number' => $validated['mobile_number'],
                'photo_path' => null,
                'password' => Hash::make($validated['password']),
                'is_active' => $request->boolean('is_active', true),
            ]);

            $user->forceFill([
                'email_verified_at' => $role !== User::ROLE_END_USER ? now() : null,
                'mobile_verified_at' => $role !== User::ROLE_END_USER ? now() : null,
                'created_by' => $request->user()->id,
            ])->save();

            Role::findOrCreate($role, 'web');
            $user->syncRoles([$role]);

            $this->upsertProfile($user, $role, $validated);

            return $user;
        });

        $this->audit->log('user.created', 'users', $user->id, $request->user(), [
            'email' => $user->email,
            'role' => $role,
        ]);

        $this->notifications->notify(
            $user,
            'admin',
            'Welcome to Pickle Ballan ni Juan',
            'Your account has been created. Please log in and update your profile.',
        );

        return back()->with('status', 'User '.$user->email.' created.');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorizeManage($request);
        $this->guardSelfDestructivePower($request, $user, 'update');

        $validated = $request->validate($this->rules($request, $user));
        $role = $validated['role'];
        $this->guardRoleAssignment($request, $role);

        DB::transaction(function () use ($request, $user, $validated, $role) {
            $user->forceFill([
                'email' => $validated['email'],
                'mobile_number' => $validated['mobile_number'],
                'is_active' => $request->boolean('is_active', $user->is_active),
            ]);

            if (! empty($validated['password'])) {
                $user->forceFill(['password' => Hash::make($validated['password'])]);
            }

            if (! $user->is_active) {
                $user->forceFill(['locked_until' => null]);
            }

            $user->save();

            $user->syncRoles([$role]);
            $this->upsertProfile($user, $role, $validated);
        });

        $this->audit->log('user.updated', 'users', $user->id, $request->user(), [
            'email' => $user->email,
            'role' => $role,
        ]);

        return back()->with('status', 'User '.$user->email.' updated.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $this->authorizeManage($request);
        $this->guardSelfDestructivePower($request, $user, 'delete');

        $email = $user->email;
        $user->update(['is_active' => false]);
        $user->delete(); // soft delete via SoftDeletes

        $this->audit->log('user.archived', 'users', $user->id, $request->user(), [
            'email' => $email,
        ]);

        return back()->with('status', 'User '.$email.' archived.');
    }

    /**
     * Trigger Laravel's password reset email for the chosen user.
     */
    public function sendPasswordReset(Request $request, User $user): RedirectResponse
    {
        $this->authorizeManage($request);

        $status = Password::sendResetLink(['email' => $user->email]);

        $message = $status === Password::RESET_LINK_SENT
            ? 'Password reset link sent to '.$user->email.'.'
            : 'Could not send reset link. Mail driver: '.config('mail.default').'.';

        $this->audit->log('user.password_reset_link', 'users', $user->id, $request->user(), [
            'status' => $status,
        ]);

        return back()->with($status === Password::RESET_LINK_SENT ? 'status' : 'error', $message);
    }

    /**
     * Toggle active flag (no-soft-delete) and clear any existing lockout.
     */
    public function toggleActive(Request $request, User $user): RedirectResponse
    {
        $this->authorizeManage($request);
        $this->guardSelfDestructivePower($request, $user, 'deactivate');

        $user->forceFill([
            'is_active' => ! $user->is_active,
            'locked_until' => null,
            'login_attempts' => 0,
        ])->save();

        $this->audit->log('user.toggle_active', 'users', $user->id, $request->user(), [
            'is_active' => $user->is_active,
        ]);

        return back()->with('status', 'User '.$user->email.' is now '.($user->is_active ? 'active' : 'disabled').'.');
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(Request $request, ?User $user): array
    {
        $isCreating = $user === null;
        $passwordRules = $isCreating
            ? ['required', 'confirmed', PasswordRule::defaults()]
            : ['nullable', 'confirmed', PasswordRule::defaults()];

        return [
            'email' => [
                'required', 'string', 'lowercase', 'email', 'max:255',
                Rule::unique(User::class, 'email')->ignore($user?->id)->whereNull('deleted_at'),
            ],
            'mobile_number' => [
                'required', 'string', 'max:20',
                Rule::unique(User::class, 'mobile_number')->ignore($user?->id)->whereNull('deleted_at'),
            ],
            'password' => $passwordRules,
            'is_active' => ['nullable', 'boolean'],
            'role' => ['required', Rule::in([
                User::ROLE_SUPER_ADMIN,
                User::ROLE_ADMIN,
                User::ROLE_LOCATION_MANAGER,
                User::ROLE_STAFF,
                User::ROLE_END_USER,
            ])],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'employee_id' => [
                'nullable', 'string', 'max:50',
                Rule::unique('staff_profiles', 'employee_id')->ignore(
                    $user ? optional(StaffProfile::query()->where('user_id', $user->id)->first())->id : null,
                ),
            ],
            'position' => ['nullable', 'string', 'max:100'],
            'hire_date' => ['nullable', 'date'],
            'assigned_location_id' => ['nullable', 'integer', 'exists:locations,id'],
            'can_confirm_payments' => ['nullable', 'boolean'],
            'can_process_refunds' => ['nullable', 'boolean'],
            'can_manage_inventory' => ['nullable', 'boolean'],
            'admin_level' => ['nullable', 'in:super,full,restricted'],
        ];
    }

    private function upsertProfile(User $user, string $role, array $validated): void
    {
        $name = [
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'middle_name' => $validated['middle_name'] ?? null,
        ];

        if (in_array($role, [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN], true)) {
            AdminProfile::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    ...$name,
                    'admin_level' => $validated['admin_level']
                        ?? ($role === User::ROLE_SUPER_ADMIN ? 'super' : 'full'),
                    'two_factor_enabled' => false,
                    'deleted_at' => null,
                ],
            );

            return;
        }

        if (in_array($role, [User::ROLE_LOCATION_MANAGER, User::ROLE_STAFF], true)) {
            StaffProfile::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    ...$name,
                    'employee_id' => $validated['employee_id'] ?: ('PBJ-'.strtoupper(Str::random(6))),
                    'position' => $validated['position'] ?? null,
                    'hire_date' => $validated['hire_date'] ?? now()->toDateString(),
                    'assigned_location_id' => $validated['assigned_location_id'] ?? null,
                    'can_confirm_payments' => (bool) ($validated['can_confirm_payments'] ?? false),
                    'can_process_refunds' => (bool) ($validated['can_process_refunds'] ?? false),
                    'can_manage_inventory' => (bool) ($validated['can_manage_inventory'] ?? false),
                    'deleted_at' => null,
                ],
            );

            return;
        }

        EndUserProfile::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                ...$name,
                'preferred_language' => 'en',
            ],
        );
    }

    private function authorizeManage(Request $request): void
    {
        abort_unless(
            $request->user()->hasAnyRole([User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN]),
            403,
        );
    }

    private function guardRoleAssignment(Request $request, string $role): void
    {
        // Only super admins can mint other super admins.
        if ($role === User::ROLE_SUPER_ADMIN
            && ! $request->user()->hasRole(User::ROLE_SUPER_ADMIN)) {
            abort(403, 'Only a super admin can assign the super admin role.');
        }
    }

    private function guardSelfDestructivePower(Request $request, User $target, string $verb): void
    {
        if ($request->user()->id === $target->id) {
            abort(403, "You cannot {$verb} your own account from User Management.");
        }

        // Don't let a non-super admin act on a super admin.
        if ($target->hasRole(User::ROLE_SUPER_ADMIN)
            && ! $request->user()->hasRole(User::ROLE_SUPER_ADMIN)) {
            abort(403, "Only a super admin can {$verb} another super admin.");
        }
    }
}
