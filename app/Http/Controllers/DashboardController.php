<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            'metrics' => $this->metricsFor($role, $user),
            'reservationHealth' => $this->reservationHealth($role, $user),
            'operationRows' => $this->operationRows($role, $user),
            'calendarSlots' => $this->calendarSlots($role, $user),
            'workQueue' => $this->workQueueFor($role, $user),
            'ordersChange' => $this->monthlyReservationChange($role, $user),
        ]);
    }

    /**
     * @return array<int, array{label: string, value: string, detail: string, icon: string, change: string}>
     */
    private function metricsFor(string $role, User $user): array
    {
        $today = now()->toDateString();
        $reservations = $this->scopedReservations($role, $user);

        return match ($role) {
            'admin' => $this->adminMetrics($reservations, $user, $today),
            'staff' => $this->staffMetrics($reservations, $user, $today),
            default => $this->customerMetrics($reservations, $user, $today),
        };
    }

    /**
     * @return array<int, array{time: string, court: string, status: string, label: string}>
     */
    private function calendarSlots(string $role, User $user): array
    {
        $today = now()->toDateString();

        $slots = $this->scopedReservations($role, $user)
            ->join('courts', 'courts.id', '=', 'reservations.court_id')
            ->join('locations', 'locations.id', '=', 'reservations.location_id')
            ->where('reservations.reservation_date', $today)
            ->orderBy('reservations.start_time')
            ->limit(7)
            ->get([
                'reservations.reservation_code',
                'reservations.start_time',
                'reservations.end_time',
                'reservations.status',
                'reservations.payment_status',
                'courts.court_number',
                'courts.court_name',
                'locations.name as location_name',
            ])
            ->map(fn ($slot) => [
                'time' => $this->timeRange($slot->start_time, $slot->end_time),
                'court' => $slot->location_name.' - Court '.$slot->court_number,
                'status' => $this->slotColor($slot->status, $slot->payment_status),
                'label' => $this->statusLabel($slot->status, $slot->payment_status).' ('.$slot->reservation_code.')',
            ]);

        if ($role !== 'end_user') {
            $maintenance = DB::table('court_maintenance')
                ->join('courts', 'courts.id', '=', 'court_maintenance.court_id')
                ->join('locations', 'locations.id', '=', 'courts.location_id')
                ->where('locations.is_active', true)
                ->whereNull('locations.deleted_at')
                ->where('courts.is_active', true)
                ->whereNull('courts.deleted_at')
                ->whereDate('court_maintenance.start_datetime', '<=', $today)
                ->whereDate('court_maintenance.end_datetime', '>=', $today)
                ->orderBy('court_maintenance.start_datetime')
                ->limit(3)
                ->get([
                    'court_maintenance.title',
                    'court_maintenance.start_datetime',
                    'court_maintenance.end_datetime',
                    'courts.court_number',
                    'locations.name as location_name',
                ])
                ->map(fn ($slot) => [
                    'time' => $this->timeRange($slot->start_datetime, $slot->end_datetime),
                    'court' => $slot->location_name.' - Court '.$slot->court_number,
                    'status' => 'maintenance',
                    'label' => $slot->title,
                ]);

            $slots = $slots->concat($maintenance)->take(7);
        }

        return $slots->values()->all();
    }

    /**
     * @return array<int, array{title: string, description: string, objective: string}>
     */
    private function workQueueFor(string $role, User $user): array
    {
        $today = now()->toDateString();
        $pendingPayments = $this->scopedPayments($role, $user)->where('payments.status', 'pending')->count();
        $checkInsDue = $this->checkInsDue($role, $user, $today);
        $returnsDue = $this->equipmentReturnsDue($role, $user);

        return match ($role) {
            'admin' => [
                ['title' => 'Payment verification', 'description' => $pendingPayments.' GCash proof(s) waiting for review.', 'objective' => 'F6-F11'],
                ['title' => 'Court and pricing setup', 'description' => DB::table('courts')->where('is_active', true)->whereNull('deleted_at')->count().' active courts with '.$this->activePricingRules().' pricing rules.', 'objective' => 'B2-B7, C1-C6'],
                ['title' => 'Reports and audit logs', 'description' => $this->activeReportsReady($today).' live branch reports and '.DB::table('audit_logs')->count().' audit entries.', 'objective' => 'L9-L11, O1-O10'],
            ],
            'staff' => [
                ['title' => 'Walk-in booking', 'description' => $this->scopedReservations($role, $user)->where('reservation_type', 'walk_in')->where('reservation_date', $today)->count().' counter booking(s) today.', 'objective' => 'H1-H7'],
                ['title' => 'Check-in search', 'description' => $checkInsDue.' confirmed booking(s) still due for arrival.', 'objective' => 'I1-I5'],
                ['title' => 'Check-out and equipment return', 'description' => $returnsDue.' equipment return item(s) still open.', 'objective' => 'I6-I9'],
            ],
            default => [
                ['title' => 'Book a court', 'description' => DB::table('courts')->where('is_active', true)->whereNull('deleted_at')->count().' courts are listed with live rates.', 'objective' => 'N3-N5'],
                ['title' => 'Payment proof', 'description' => $pendingPayments.' payment proof item(s) waiting for staff action.', 'objective' => 'F1-F5'],
                ['title' => 'Receipt and review', 'description' => $this->scopedPayments($role, $user)->where('payments.status', 'verified')->count().' paid receipt(s) available.', 'objective' => 'G1-G7, K1-K10'],
            ],
        };
    }

    /**
     * @return array<int, array{label: string, value: int}>
     */
    private function reservationHealth(string $role, User $user): array
    {
        $reservations = $this->scopedReservations($role, $user)
            ->whereBetween('reservation_date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()]);

        $confirmed = (clone $reservations)
            ->whereIn('status', ['confirmed', 'checked_in', 'ongoing', 'completed'])
            ->count();
        $pending = (clone $reservations)
            ->whereIn('payment_status', ['unpaid', 'pending_verification', 'partially_paid'])
            ->count();
        $blocked = (clone $reservations)
            ->whereIn('status', ['cancelled', 'no_show', 'refunded'])
            ->count();

        if ($role !== 'end_user') {
            $blocked += DB::table('court_maintenance')
                ->whereBetween('start_datetime', [now()->startOfMonth(), now()->endOfMonth()])
                ->count();
        }

        $total = max(1, $confirmed + $pending + $blocked);

        return [
            ['label' => 'Confirmed reservations', 'value' => $this->percent($confirmed, $total)],
            ['label' => 'Pending payments', 'value' => $this->percent($pending, $total)],
            ['label' => 'Cancelled or blocked slots', 'value' => $this->percent($blocked, $total)],
        ];
    }

    /**
     * @return array<int, array{name: string, budget: string, progress: int, color: string, icon: string}>
     */
    private function operationRows(string $role, User $user): array
    {
        $today = now()->toDateString();
        $reservations = $this->scopedReservations($role, $user);
        $todayBookings = (clone $reservations)->where('reservation_date', $today)->count();
        $verifiedPaymentsToday = $this->scopedPayments($role, $user)
            ->whereDate('payments.created_at', $today)
            ->whereIn('payments.status', ['verified', 'refunded'])
            ->count();
        $paymentsToday = max(1, $this->scopedPayments($role, $user)->whereDate('payments.created_at', $today)->count());
        $availableInventory = (int) $this->activeInventory()->sum('equipment_inventory.available_quantity');
        $totalInventory = max(1, (int) $this->activeInventory()->sum('equipment_inventory.total_quantity'));
        $reportsReady = $this->activeReportsReady($today);
        $locations = max(1, DB::table('locations')->where('is_active', true)->whereNull('deleted_at')->count());

        return [
            ['name' => 'Online court bookings', 'budget' => $todayBookings.' today', 'progress' => min(100, $todayBookings * 12), 'color' => 'info', 'icon' => 'fa-calendar-check'],
            ['name' => 'Payment confirmation', 'budget' => $this->scopedPayments($role, $user)->where('payments.status', 'pending')->count().' queued', 'progress' => $this->percent($verifiedPaymentsToday, $paymentsToday), 'color' => 'info', 'icon' => 'fa-money-check-alt'],
            ['name' => 'Walk-in handling', 'budget' => (clone $reservations)->where('reservation_date', $today)->where('reservation_type', 'walk_in')->count().' counter', 'progress' => $this->percent((clone $reservations)->where('reservation_date', $today)->where('reservation_type', 'walk_in')->where('payment_status', 'paid')->count(), max(1, (clone $reservations)->where('reservation_date', $today)->where('reservation_type', 'walk_in')->count())), 'color' => 'success', 'icon' => 'fa-walking'],
            ['name' => 'Equipment inventory', 'budget' => $availableInventory.' available', 'progress' => $this->percent($availableInventory, $totalInventory), 'color' => 'success', 'icon' => 'fa-boxes'],
            ['name' => 'Reports and ratings', 'budget' => $reportsReady.'/'.$locations.' branches', 'progress' => $this->percent($reportsReady, $locations), 'color' => 'info', 'icon' => 'fa-chart-pie'],
        ];
    }

    /**
     * @return array<int, array{label: string, value: string, detail: string, icon: string, change: string}>
     */
    private function adminMetrics(Builder $reservations, User $user, string $today): array
    {
        $bookingsToday = (clone $reservations)
            ->where('reservation_date', $today)
            ->whereNotIn('status', ['cancelled', 'refunded'])
            ->count();
        $pendingPayments = $this->scopedPayments('admin', $user)->where('payments.status', 'pending')->count();
        $revenueToday = $this->scopedPayments('admin', $user)
            ->whereDate('payments.created_at', $today)
            ->whereIn('payments.status', ['verified', 'refunded'])
            ->sum('payments.amount');
        $utilization = (float) DB::table('daily_reports_aggregates')
            ->join('locations', 'locations.id', '=', 'daily_reports_aggregates.location_id')
            ->where('locations.is_active', true)
            ->whereNull('locations.deleted_at')
            ->where('report_date', $today)
            ->avg('utilization_rate');

        return [
            ['label' => "Today's bookings", 'value' => (string) $bookingsToday, 'detail' => 'Active reservation volume', 'icon' => 'fa-calendar-check', 'change' => $this->countChange($bookingsToday, (clone $reservations)->where('reservation_date', now()->subDay()->toDateString())->count())],
            ['label' => 'Pending payments', 'value' => (string) $pendingPayments, 'detail' => 'GCash queue', 'icon' => 'fa-receipt', 'change' => $pendingPayments > 0 ? 'Live' : 'Clear'],
            ['label' => 'Revenue today', 'value' => $this->money($revenueToday), 'detail' => 'Verified payments', 'icon' => 'fa-coins', 'change' => $this->countChange((int) $revenueToday, (int) DB::table('payments')->whereDate('created_at', now()->subDay()->toDateString())->whereIn('status', ['verified', 'refunded'])->sum('amount'))],
            ['label' => 'Utilization', 'value' => round($utilization).'%', 'detail' => 'Average branch use', 'icon' => 'fa-chart-line', 'change' => 'Live'],
        ];
    }

    /**
     * @return array<int, array{label: string, value: string, detail: string, icon: string, change: string}>
     */
    private function staffMetrics(Builder $reservations, User $user, string $today): array
    {
        return [
            ['label' => "Today's schedule", 'value' => (string) (clone $reservations)->where('reservation_date', $today)->count(), 'detail' => 'Assigned branch bookings', 'icon' => 'fa-calendar-day', 'change' => 'Today'],
            ['label' => 'Check-ins due', 'value' => (string) $this->checkInsDue('staff', $user, $today), 'detail' => 'Confirmed arrivals pending', 'icon' => 'fa-clipboard-check', 'change' => 'Live'],
            ['label' => 'Payments to verify', 'value' => (string) $this->scopedPayments('staff', $user)->where('payments.status', 'pending')->count(), 'detail' => 'Uploaded proofs', 'icon' => 'fa-money-check', 'change' => 'Queue'],
            ['label' => 'Equipment returns', 'value' => (string) $this->equipmentReturnsDue('staff', $user), 'detail' => 'Rental items open', 'icon' => 'fa-table-tennis', 'change' => 'Open'],
        ];
    }

    /**
     * @return array<int, array{label: string, value: string, detail: string, icon: string, change: string}>
     */
    private function customerMetrics(Builder $reservations, User $user, string $today): array
    {
        $profile = DB::table('end_user_profiles')->where('user_id', $user->id)->first();

        return [
            ['label' => 'Upcoming bookings', 'value' => (string) (clone $reservations)->where('reservation_date', '>=', $today)->whereNotIn('status', ['cancelled', 'refunded'])->count(), 'detail' => 'Future court time', 'icon' => 'fa-calendar', 'change' => 'Active'],
            ['label' => 'Pending payments', 'value' => (string) (clone $reservations)->whereIn('payment_status', ['unpaid', 'pending_verification'])->count(), 'detail' => 'Proof or payment needed', 'icon' => 'fa-wallet', 'change' => 'Live'],
            ['label' => 'Loyalty points', 'value' => number_format((int) ($profile->loyalty_points ?? 0)), 'detail' => 'Earned from completed play', 'icon' => 'fa-star', 'change' => '+Earn'],
            ['label' => 'Total spent', 'value' => $this->money($profile->total_spent ?? 0), 'detail' => 'Lifetime paid amount', 'icon' => 'fa-chart-line', 'change' => 'History'],
        ];
    }

    private function scopedReservations(string $role, User $user): Builder
    {
        $query = DB::table('reservations')
            ->whereNull('reservations.deleted_at')
            ->whereIn('reservations.location_id', $this->activeLocationIds());

        if ($role === 'end_user') {
            $query->where('reservations.user_id', $user->id);
        }

        if ($role === 'staff' && $locationId = $this->assignedLocationId($user)) {
            $query->where('reservations.location_id', $locationId);
        }

        return $query;
    }

    private function scopedPayments(string $role, User $user): Builder
    {
        $query = DB::table('payments')
            ->join('reservations', 'reservations.id', '=', 'payments.reservation_id')
            ->whereNull('reservations.deleted_at')
            ->whereIn('reservations.location_id', $this->activeLocationIds());

        if ($role === 'end_user') {
            $query->where('reservations.user_id', $user->id);
        }

        if ($role === 'staff' && $locationId = $this->assignedLocationId($user)) {
            $query->where('reservations.location_id', $locationId);
        }

        return $query;
    }

    private function checkInsDue(string $role, User $user, string $today): int
    {
        return $this->scopedReservations($role, $user)
            ->where('reservations.reservation_date', $today)
            ->whereIn('reservations.status', ['confirmed'])
            ->where('reservations.payment_status', 'paid')
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('check_in_logs')
                    ->whereColumn('check_in_logs.reservation_id', 'reservations.id');
            })
            ->count();
    }

    private function equipmentReturnsDue(string $role, User $user): int
    {
        $query = DB::table('reservation_equipment')
            ->join('reservations', 'reservations.id', '=', 'reservation_equipment.reservation_id')
            ->whereNull('reservations.deleted_at')
            ->whereIn('reservations.location_id', $this->activeLocationIds())
            ->whereIn('reservations.status', ['checked_in', 'ongoing'])
            ->where('reservation_equipment.is_returned', false);

        if ($role === 'staff' && $locationId = $this->assignedLocationId($user)) {
            $query->where('reservations.location_id', $locationId);
        }

        if ($role === 'end_user') {
            $query->where('reservations.user_id', $user->id);
        }

        return $query->sum('reservation_equipment.quantity');
    }

    private function assignedLocationId(User $user): ?int
    {
        return DB::table('staff_profiles')->where('user_id', $user->id)->value('assigned_location_id');
    }

    /**
     * @return array<int, int>
     */
    private function activeLocationIds(): array
    {
        $ids = DB::table('locations')
            ->where('is_active', true)
            ->whereNull('deleted_at')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return $ids ?: [0];
    }

    private function activePricingRules(): int
    {
        return DB::table('court_pricing_rules')
            ->join('courts', 'courts.id', '=', 'court_pricing_rules.court_id')
            ->where('court_pricing_rules.is_active', true)
            ->where('courts.is_active', true)
            ->whereNull('courts.deleted_at')
            ->count();
    }

    private function activeReportsReady(string $date): int
    {
        return DB::table('daily_reports_aggregates')
            ->join('locations', 'locations.id', '=', 'daily_reports_aggregates.location_id')
            ->where('locations.is_active', true)
            ->whereNull('locations.deleted_at')
            ->where('daily_reports_aggregates.report_date', $date)
            ->count();
    }

    private function activeInventory(): Builder
    {
        return DB::table('equipment_inventory')
            ->join('locations', 'locations.id', '=', 'equipment_inventory.location_id')
            ->where('locations.is_active', true)
            ->whereNull('locations.deleted_at');
    }

    private function monthlyReservationChange(string $role, User $user): int
    {
        $current = $this->scopedReservations($role, $user)
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();
        $previous = $this->scopedReservations($role, $user)
            ->whereBetween('created_at', [now()->subMonthNoOverflow()->startOfMonth(), now()->subMonthNoOverflow()->endOfMonth()])
            ->count();

        return $previous === 0 ? ($current > 0 ? 100 : 0) : (int) round((($current - $previous) / $previous) * 100);
    }

    private function percent(int|float $value, int|float $total): int
    {
        return (int) round(($value / max(1, $total)) * 100);
    }

    private function money(int|float|null $amount): string
    {
        return 'PHP '.number_format((float) $amount, 2);
    }

    private function countChange(int|float $current, int|float $previous): string
    {
        if ((float) $previous === 0.0) {
            return $current > 0 ? '+100%' : '0%';
        }

        $change = (($current - $previous) / $previous) * 100;

        return ($change >= 0 ? '+' : '').round($change).'%';
    }

    private function timeRange(string $start, string $end): string
    {
        return date('H:i', strtotime($start)).' - '.date('H:i', strtotime($end));
    }

    private function slotColor(string $status, string $paymentStatus): string
    {
        return match (true) {
            in_array($status, ['cancelled', 'no_show', 'refunded'], true) => 'maintenance',
            in_array($paymentStatus, ['unpaid', 'pending_verification'], true) => 'pending',
            in_array($status, ['confirmed', 'checked_in', 'ongoing', 'completed'], true) => 'booked',
            default => 'available',
        };
    }

    private function statusLabel(string $status, string $paymentStatus): string
    {
        return match (true) {
            $status === 'pending_payment' => 'Pending payment',
            $status === 'payment_verification' => 'Payment verification',
            $status === 'checked_in' => 'Checked in',
            $status === 'completed' => 'Completed',
            $status === 'cancelled' => 'Cancelled',
            $status === 'no_show' => 'No-show',
            $paymentStatus === 'paid' => 'Booked and paid',
            default => ucfirst(str_replace('_', ' ', $status)),
        };
    }
}
