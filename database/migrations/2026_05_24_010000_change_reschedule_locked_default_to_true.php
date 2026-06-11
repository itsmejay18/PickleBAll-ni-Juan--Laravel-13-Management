<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('UPDATE `reservations` SET `reschedule_locked` = 1 WHERE `reschedule_locked` IS NULL OR `reschedule_locked` = 0');

        if (Schema::hasColumn('reservations', 'reschedule_locked')) {
            if (DB::getDriverName() === 'sqlite') {
                Schema::table('reservations', function (Blueprint $table) {
                    $table->boolean('reschedule_locked')->default(true)->change();
                });
            } else {
                DB::statement('ALTER TABLE `reservations` MODIFY `reschedule_locked` tinyint(1) NOT NULL DEFAULT 1');
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('reservations', 'reschedule_locked')) {
            if (DB::getDriverName() === 'sqlite') {
                Schema::table('reservations', function (Blueprint $table) {
                    $table->boolean('reschedule_locked')->default(false)->change();
                });
            } else {
                DB::statement('ALTER TABLE `reservations` MODIFY `reschedule_locked` tinyint(1) NOT NULL DEFAULT 0');
            }
        }
    }
};
