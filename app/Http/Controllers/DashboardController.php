<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        $role = match (true) {
            $user->hasRole(User::ROLE_SUPER_ADMIN),
            $user->hasRole(User::ROLE_ADMIN) => 'admin',
            $user->hasRole(User::ROLE_LOCATION_MANAGER),
            $user->hasRole(User::ROLE_STAFF) => 'staff',
            default => 'end_user',
        };

        return view('dashboard', [
            'role' => $role,
            'metrics' => $this->metricsFor($role),
            'calendarSlots' => $this->calendarSlots(),
            'workQueue' => $this->workQueueFor($role),
        ]);
    }

    /**
     * Placeholder metrics map directly to the approved objectives.
     *
     * @return array<int, array{label: string, value: string, detail: string, icon: string}>
     */
    private function metricsFor(string $role): array
    {
        return match ($role) {
            'admin' => [
                ['label' => "Today's bookings", 'value' => '0', 'detail' => 'L1, O2', 'icon' => 'fa-calendar-check'],
                ['label' => 'Pending payments', 'value' => '0', 'detail' => 'F6, L6', 'icon' => 'fa-receipt'],
                ['label' => 'Revenue today', 'value' => 'PHP 0.00', 'detail' => 'O1', 'icon' => 'fa-coins'],
                ['label' => 'Utilization', 'value' => '0%', 'detail' => 'O8', 'icon' => 'fa-chart-line'],
            ],
            'staff' => [
                ['label' => "Today's schedule", 'value' => '0', 'detail' => 'M1, M2', 'icon' => 'fa-calendar-day'],
                ['label' => 'Check-ins due', 'value' => '0', 'detail' => 'I1-I5', 'icon' => 'fa-clipboard-check'],
                ['label' => 'Payments to verify', 'value' => '0', 'detail' => 'M4', 'icon' => 'fa-money-check'],
                ['label' => 'Equipment returns', 'value' => '0', 'detail' => 'I6-I9', 'icon' => 'fa-table-tennis-paddle-ball'],
            ],
            default => [
                ['label' => 'Upcoming bookings', 'value' => '0', 'detail' => 'N2, N10', 'icon' => 'fa-calendar'],
                ['label' => 'Pending payments', 'value' => '0', 'detail' => 'N5', 'icon' => 'fa-wallet'],
                ['label' => 'Loyalty points', 'value' => '0', 'detail' => 'A5', 'icon' => 'fa-star'],
                ['label' => 'Total spent', 'value' => 'PHP 0.00', 'detail' => 'N2', 'icon' => 'fa-chart-simple'],
            ],
        };
    }

    /**
     * @return array<int, array{time: string, court: string, status: string, label: string}>
     */
    private function calendarSlots(): array
    {
        return [
            ['time' => '08:00', 'court' => 'Court 1', 'status' => 'available', 'label' => 'Available'],
            ['time' => '09:00', 'court' => 'Court 1', 'status' => 'pending', 'label' => 'Pending Payment'],
            ['time' => '10:00', 'court' => 'Court 2', 'status' => 'booked', 'label' => 'Booked & Paid'],
            ['time' => '11:00', 'court' => 'Court 3', 'status' => 'maintenance', 'label' => 'Maintenance'],
        ];
    }

    /**
     * @return array<int, array{title: string, description: string, objective: string}>
     */
    private function workQueueFor(string $role): array
    {
        return match ($role) {
            'admin' => [
                ['title' => 'Payment verification', 'description' => 'Review GCash screenshots and reference numbers.', 'objective' => 'F6-F11'],
                ['title' => 'Court and pricing setup', 'description' => 'Manage courts, schedules, peak rates, and maintenance closures.', 'objective' => 'B2-B7, C1-C6'],
                ['title' => 'Reports and audit logs', 'description' => 'Track revenue, no-shows, inventory, and system actions.', 'objective' => 'L9-L11, O1-O10'],
            ],
            'staff' => [
                ['title' => 'Walk-in booking', 'description' => 'Create reservations and collect cash or manual GCash payments.', 'objective' => 'H1-H7'],
                ['title' => 'Check-in search', 'description' => 'Find reservations by code, customer, court, date, or time.', 'objective' => 'I1-I5'],
                ['title' => 'Check-out and equipment return', 'description' => 'Log returned, damaged, or lost equipment and additional charges.', 'objective' => 'I6-I9'],
            ],
            default => [
                ['title' => 'Book a court', 'description' => 'Choose location, court, time slot, and equipment.', 'objective' => 'N3-N5'],
                ['title' => 'Payment proof', 'description' => 'Upload GCash screenshot and reference number.', 'objective' => 'F1-F5'],
                ['title' => 'Receipt and review', 'description' => 'View digital receipt and rate completed bookings.', 'objective' => 'G1-G7, K1-K10'],
            ],
        };
    }
}
