<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportController extends Controller
{
    /**
     * CSV exports for objective O10. Type: revenue|bookings|cancellations|no-shows|equipment.
     */
    public function csv(Request $request, string $type): StreamedResponse
    {
        $this->authorizeAdmin($request);

        $from = $request->date('from') ?? now()->startOfMonth();
        $to = $request->date('to') ?? now()->endOfMonth();
        $filename = "pbj-{$type}-{$from->toDateString()}-to-{$to->toDateString()}.csv";

        $rows = match ($type) {
            'revenue' => $this->revenueRows($from, $to),
            'bookings' => $this->bookingRows($from, $to),
            'cancellations' => $this->cancellationRows($from, $to),
            'no-shows' => $this->noShowRows($from, $to),
            'equipment' => $this->equipmentRows($from, $to),
            default => abort(404, 'Unknown report type.'),
        };

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            $headerWritten = false;

            foreach ($rows as $row) {
                if (! $headerWritten) {
                    fputcsv($out, array_keys($row));
                    $headerWritten = true;
                }
                fputcsv($out, array_values($row));
            }

            if (! $headerWritten) {
                fputcsv($out, ['No data in selected range.']);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function revenueRows(Carbon $from, Carbon $to): array
    {
        return DB::table('daily_reports_aggregates as dra')
            ->join('locations as l', 'l.id', '=', 'dra.location_id')
            ->whereBetween('dra.report_date', [$from->toDateString(), $to->toDateString()])
            ->orderBy('dra.report_date')
            ->orderBy('l.name')
            ->get([
                'dra.report_date',
                'l.name as location',
                'dra.total_reservations',
                'dra.total_online_reservations',
                'dra.total_walkin_reservations',
                'dra.total_revenue',
                'dra.total_gcash_revenue',
                'dra.total_cash_revenue',
                'dra.utilization_rate',
                'dra.average_rating',
            ])
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function bookingRows(Carbon $from, Carbon $to): array
    {
        return DB::table('reservations as r')
            ->join('courts as c', 'c.id', '=', 'r.court_id')
            ->join('locations as l', 'l.id', '=', 'r.location_id')
            ->join('users as u', 'u.id', '=', 'r.user_id')
            ->leftJoin('end_user_profiles as eup', 'eup.user_id', '=', 'u.id')
            ->whereBetween('r.reservation_date', [$from->toDateString(), $to->toDateString()])
            ->whereNull('r.deleted_at')
            ->orderBy('r.reservation_date')
            ->orderBy('r.start_time')
            ->get([
                'r.reservation_code',
                'l.name as location',
                'c.court_number',
                'r.reservation_date',
                'r.start_time',
                'r.end_time',
                'r.reservation_type',
                'r.status',
                'r.payment_status',
                'r.grand_total',
                'u.email as customer_email',
                DB::raw("COALESCE(NULLIF(TRIM(CONCAT(COALESCE(eup.first_name,''),' ',COALESCE(eup.last_name,''))),''), u.email) as customer_name"),
            ])
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function cancellationRows(Carbon $from, Carbon $to): array
    {
        return DB::table('cancellation_logs as cl')
            ->join('reservations as r', 'r.id', '=', 'cl.reservation_id')
            ->join('users as u', 'u.id', '=', 'cl.cancelled_by')
            ->whereBetween('cl.cancelled_at', [$from->startOfDay(), $to->endOfDay()])
            ->orderByDesc('cl.cancelled_at')
            ->get([
                'r.reservation_code',
                'cl.cancelled_at',
                'cl.reason_category',
                'cl.reason_text',
                'cl.refund_amount',
                'u.email as cancelled_by',
            ])
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function noShowRows(Carbon $from, Carbon $to): array
    {
        return DB::table('reservations as r')
            ->join('courts as c', 'c.id', '=', 'r.court_id')
            ->join('locations as l', 'l.id', '=', 'r.location_id')
            ->join('users as u', 'u.id', '=', 'r.user_id')
            ->where('r.status', 'no_show')
            ->whereBetween('r.reservation_date', [$from->toDateString(), $to->toDateString()])
            ->whereNull('r.deleted_at')
            ->orderByDesc('r.reservation_date')
            ->get([
                'r.reservation_code',
                'r.reservation_date',
                'r.start_time',
                'r.end_time',
                'l.name as location',
                'c.court_number',
                'r.grand_total',
                'u.email as customer_email',
            ])
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function equipmentRows(Carbon $from, Carbon $to): array
    {
        return DB::table('reservation_equipment as re')
            ->join('reservations as r', 'r.id', '=', 're.reservation_id')
            ->join('equipment_types as et', 'et.id', '=', 're.equipment_type_id')
            ->join('locations as l', 'l.id', '=', 'r.location_id')
            ->whereBetween('r.reservation_date', [$from->toDateString(), $to->toDateString()])
            ->whereNull('r.deleted_at')
            ->groupBy('et.id', 'et.name', 'l.name')
            ->orderBy('l.name')
            ->orderBy('et.name')
            ->get([
                'l.name as location',
                'et.name as equipment',
                DB::raw('SUM(re.quantity) as total_units_rented'),
                DB::raw('SUM(re.quantity * re.price_per_unit) as total_rental_revenue'),
            ])
            ->map(fn ($row) => (array) $row)
            ->all();
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless(
            $request->user()->hasAnyRole([User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN]),
            403,
        );
    }
}
