<?php

use App\Console\Commands\AggregateDailyReports;
use App\Console\Commands\ExpirePendingReservations;
use App\Console\Commands\MarkNoShowReservations;
use App\Console\Commands\NotifyLowStock;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Auto-release expired pending reservations every 10 minutes (E10).
Schedule::command(ExpirePendingReservations::class)
    ->everyTenMinutes()
    ->withoutOverlapping();

// Mark no-shows once per hour after operating hours (J8).
Schedule::command(MarkNoShowReservations::class)
    ->hourly()
    ->withoutOverlapping();

// Aggregate yesterday's reports just after midnight (O1-O10).
Schedule::command(AggregateDailyReports::class)
    ->dailyAt('00:30')
    ->withoutOverlapping();

// Daily low-stock alerts (D6).
Schedule::command(NotifyLowStock::class)
    ->dailyAt('07:00')
    ->withoutOverlapping();
