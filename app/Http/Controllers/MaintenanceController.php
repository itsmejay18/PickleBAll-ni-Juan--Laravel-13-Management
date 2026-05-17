<?php

namespace App\Http\Controllers;

use App\Models\CourtMaintenance;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MaintenanceController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeManage($request);

        $validated = $request->validate([
            'court_id' => ['required', 'integer', 'exists:courts,id'],
            'maintenance_type' => ['required', 'in:regular,emergency,tournament,private_event,holiday'],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'start_datetime' => ['required', 'date'],
            'end_datetime' => ['required', 'date', 'after:start_datetime'],
            'is_all_day' => ['nullable', 'boolean'],
            'recurring_weekly' => ['nullable', 'boolean'],
            'recurring_end_date' => ['nullable', 'date', 'after_or_equal:end_datetime'],
        ]);

        $maintenance = CourtMaintenance::query()->create([
            ...$validated,
            'is_all_day' => $request->boolean('is_all_day'),
            'recurring_weekly' => $request->boolean('recurring_weekly'),
            'approved_by' => $request->user()->id,
            'created_by' => $request->user()->id,
        ]);

        $this->audit->log('maintenance.created', 'court_maintenance', $maintenance->id, $request->user(), $validated);

        return back()->with('status', 'Maintenance window created.');
    }

    public function update(Request $request, CourtMaintenance $maintenance): RedirectResponse
    {
        $this->authorizeManage($request);

        $validated = $request->validate([
            'maintenance_type' => ['required', 'in:regular,emergency,tournament,private_event,holiday'],
            'title' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:2000'],
            'start_datetime' => ['required', 'date'],
            'end_datetime' => ['required', 'date', 'after:start_datetime'],
            'is_all_day' => ['nullable', 'boolean'],
            'recurring_weekly' => ['nullable', 'boolean'],
            'recurring_end_date' => ['nullable', 'date', 'after_or_equal:end_datetime'],
        ]);

        $maintenance->update([
            ...$validated,
            'is_all_day' => $request->boolean('is_all_day'),
            'recurring_weekly' => $request->boolean('recurring_weekly'),
        ]);

        $this->audit->log('maintenance.updated', 'court_maintenance', $maintenance->id, $request->user(), $validated);

        return back()->with('status', 'Maintenance window updated.');
    }

    public function destroy(Request $request, CourtMaintenance $maintenance): RedirectResponse
    {
        $this->authorizeManage($request);

        $id = $maintenance->id;
        $title = $maintenance->title;
        $maintenance->delete();

        $this->audit->log('maintenance.deleted', 'court_maintenance', $id, $request->user(), [
            'title' => $title,
        ]);

        return back()->with('status', 'Maintenance window removed.');
    }

    private function authorizeManage(Request $request): void
    {
        abort_unless(
            $request->user()->hasAnyRole([User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN, User::ROLE_LOCATION_MANAGER]),
            403,
        );
    }
}
