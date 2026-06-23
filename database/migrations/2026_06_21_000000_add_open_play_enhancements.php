<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->integer('rating')->default(1000)->after('is_active');
            $table->integer('wins')->default(0)->after('rating');
            $table->integer('losses')->default(0)->after('wins');
            $table->integer('matches_played')->default(0)->after('losses');
        });

        Schema::table('open_play_settings', function (Blueprint $table) {
            $table->time('attendance_closing_time')->default('17:55:00')->after('open_play_end');
        });

        Schema::table('open_play_events', function (Blueprint $table) {
            $table->time('attendance_closing_time')->default('17:55:00')->after('end_time');
        });

        Schema::table('open_play_matches', function (Blueprint $table) {
            $table->unsignedTinyInteger('winner_team')->nullable()->after('team2_player2_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['rating', 'wins', 'losses', 'matches_played']);
        });

        Schema::table('open_play_settings', function (Blueprint $table) {
            $table->dropColumn('attendance_closing_time');
        });

        Schema::table('open_play_events', function (Blueprint $table) {
            $table->dropColumn('attendance_closing_time');
        });

        Schema::table('open_play_matches', function (Blueprint $table) {
            $table->dropColumn('winner_team');
        });
    }
};
