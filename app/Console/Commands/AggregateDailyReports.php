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
            $reservations = DB::table('reservations')
                ->where('location_id', $locationId)
                ->where('reservation_date', $dateStr)
                ->whereNull('deleted_at')
                ->get();

            $totalReservations = $reservations->count();
            $online = $reservations->where('reservation_type', 'online')->count();
            $walkin = $reservations->where('reservation_type', 'walk_in')->count();
            $cancellations = $reservations->where('status', 'cancelled')->count();
            $noShows = $reservations->where('status', 'no_show')->count();

            $verifiedPayments = DB::table('payments as p')
                ->join('reservations as r', 'r.id', '=', 'p.reservation_id')
                ->where('r.location_id', $locationId)
                ->where('r.reservation_date', $dateStr)
                ->where('p.status', 'verified')
                ->whereNull('r.deleted_at')
                ->select('p.amount', 'p.payment_method')
                ->get();

            $revenue = $verifiedPayments->sum('amount');
            $gcash = $verifiedPayments->where('payment_method', 'gcash')->sum('amount');
            $cash = $verifiedPayments->where('payment_method', 'cash')->sum('amount');

            $rating = (float) DB::table('ratings')
                ->whereIn('reservation_id', $reservations->pluck('id'))
                ->avg('rating_score');

            $peakHour = DB::table('reservations')
                ->where('location_id', $locationId)
                ->where('reservation_date', $dateStr)
                ->whereNull('deleted_at')
                ->groupBy('start_time')
                ->orderByRaw('COUNT(*) DESC')
                ->limit(1)
                ->select('start_time', 'end_time')
                ->first();

            $courtHours = DB::table('court_schedules as cs')
                ->join('courts as c', 'c.id', '=', 'cs.court_id')
                ->where('c.location_id', $locationId)
                ->where('c.is_active', true)
                ->whereNull('c.deleted_at')
                ->where('cs.day_of_week', $date->dayOfWeek)
                ->get()
                ->sum(fn ($s) => max(0, (strtotime($s->close_time) - strtotime($s->open_time)) / 3600));

            $bookedHours = $reservations->whereIn('status', ['confirmed', 'checked_in', 'ongoing', 'completed'])->sum(function ($r) {
                return max(0, (strtotime($r->end_time) - strtotime($r->start_time)) / 3600);
            });

            $utilisation = $courtHours > 0 ? round(($bookedHours / $courtHours) * 100, 2) : null;

            DB::table('daily_reports_aggregates')->updateOrInsert(
                ['report_date' => $dateStr, 'location_id' => $locationId],
                [
                    'total_reservations' => $totalReservations,
                    'total_online_reservations' => $online,
                    'total_walkin_reservations' => $walkin,
                    'total_cancellations' => $cancellations,
                    'total_no_shows' => $noShows,
                    'total_revenue' => $revenue,
                    'total_gcash_revenue' => $gcash,
                    'total_cash_revenue' => $cash,
                    'average_rating' => $rating ?: null,
                    'peak_hour_start' => $peakHour?->start_time,
                    'peak_hour_end' => $peakHour?->end_time,
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
