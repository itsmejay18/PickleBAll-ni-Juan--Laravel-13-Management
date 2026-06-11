<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AggregateDailyReports extends Command
{
    protected $signature = 'reports:aggregate-daily {date?}';

    protected $description = 'Roll up bookings, revenue, no-shows, ratings, and utilisation per location into daily_reports_aggregates.';

    public function handle(): int
    {
        $date = $this->argument('date') ? Carbon::parse($this->argument('date')) : now()->subDay();
        $dateStr = $date->toDateString();

        $locations = DB::table('locations')->where('is_active', true)->whereNull('deleted_at')->pluck('id');

        foreach ($locations as $locationId) {
            // W9 fix: use SQL aggregates instead of loading all rows into memory
            $counts = DB::table('reservations')
                ->where('location_id', $locationId)
                ->where('reservation_date', $dateStr)
                ->whereNull('deleted_at')
                ->selectRaw("
                    COUNT(*) as total_reservations,
                    SUM(CASE WHEN reservation_type = 'online' THEN 1 ELSE 0 END) as online,
                    SUM(CASE WHEN reservation_type = 'walk_in' THEN 1 ELSE 0 END) as walkin,
                    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancellations,
                    SUM(CASE WHEN status = 'no_show' THEN 1 ELSE 0 END) as no_shows
                ")
                ->first();

            $revenueData = DB::table('payments as p')
                ->join('reservations as r', 'r.id', '=', 'p.reservation_id')
                ->where('r.location_id', $locationId)
                ->where('r.reservation_date', $dateStr)
                ->where('p.status', 'verified')
                ->whereNull('r.deleted_at')
                ->selectRaw("
                    SUM(p.amount) as revenue,
                    SUM(CASE WHEN p.payment_method = 'gcash' THEN p.amount ELSE 0 END) as gcash,
                    SUM(CASE WHEN p.payment_method = 'cash' THEN p.amount ELSE 0 END) as cash
                ")
                ->first();

            $rating = (float) DB::table('ratings as rt')
                ->join('reservations as r', 'r.id', '=', 'rt.reservation_id')
                ->where('r.location_id', $locationId)
                ->where('r.reservation_date', $dateStr)
                ->whereNull('r.deleted_at')
                ->avg('rt.rating_score');

            // I12 fix: peak hour is the start_time with the most bookings; end = start + 1 hour
            $peakHour = DB::table('reservations')
                ->where('location_id', $locationId)
                ->where('reservation_date', $dateStr)
                ->whereNull('deleted_at')
                ->groupBy('start_time')
                ->orderByRaw('COUNT(*) DESC')
                ->limit(1)
                ->selectRaw("start_time, TIME(ADDTIME(start_time, '01:00:00')) as peak_end")
                ->first();

            $courtHours = DB::table('court_schedules as cs')
                ->join('courts as c', 'c.id', '=', 'cs.court_id')
                ->where('c.location_id', $locationId)
                ->where('c.is_active', true)
                ->whereNull('c.deleted_at')
                ->where('cs.day_of_week', $date->dayOfWeek)
                ->get()
                ->sum(fn ($s) => max(0, (strtotime($s->close_time) - strtotime($s->open_time)) / 3600));

            // Booked hours via SQL sum
            $bookedHours = (float) DB::table('reservations')
                ->where('location_id', $locationId)
                ->where('reservation_date', $dateStr)
                ->whereNull('deleted_at')
                ->whereIn('status', ['confirmed', 'checked_in', 'ongoing', 'completed'])
                ->selectRaw('SUM((UNIX_TIMESTAMP(end_time) - UNIX_TIMESTAMP(start_time)) / 3600) as hours')
                ->value('hours');

            $utilisation = $courtHours > 0 ? round(($bookedHours / $courtHours) * 100, 2) : null;

            DB::table('daily_reports_aggregates')->updateOrInsert(
                ['report_date' => $dateStr, 'location_id' => $locationId],
                [
                    'total_reservations' => $counts->total_reservations ?? 0,
                    'total_online_reservations' => $counts->online ?? 0,
                    'total_walkin_reservations' => $counts->walkin ?? 0,
                    'total_cancellations' => $counts->cancellations ?? 0,
                    'total_no_shows' => $counts->no_shows ?? 0,
                    'total_revenue' => $revenueData->revenue ?? 0,
                    'total_gcash_revenue' => $revenueData->gcash ?? 0,
                    'total_cash_revenue' => $revenueData->cash ?? 0,
                    'average_rating' => $rating ?: null,
                    'peak_hour_start' => $peakHour?->start_time,
                    'peak_hour_end' => $peakHour?->peak_end,
                    'utilization_rate' => $utilisation,
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }

        $this->info("Aggregated daily reports for {$dateStr}.");

        return self::SUCCESS;
    }
}
