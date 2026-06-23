<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Singleton configuration row for Open Play (toggle, schedule, pricing, slots).
        Schema::create('open_play_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_enabled')->default(false);
            $table->unsignedTinyInteger('day_of_week')->default(5); // 0=Sun ... 5=Fri
            $table->time('open_play_start')->default('18:00:00');
            $table->time('open_play_end')->default('22:00:00');
            $table->time('booking_open_start')->default('05:00:00'); // normal booking before open play
            $table->time('booking_open_end')->default('17:00:00');
            $table->time('booking_resume_start')->default('23:00:00'); // normal booking after open play
            $table->time('booking_resume_end')->default('00:00:00');
            $table->decimal('entrance_fee', 10, 2)->default(0);
            $table->unsignedInteger('max_slots')->default(40);
            $table->unsignedTinyInteger('rounds')->default(3);
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        // Concrete Open Play instance for a specific date (mirrors settings at creation, editable).
        Schema::create('open_play_events', function (Blueprint $table) {
            $table->id();
            $table->date('event_date');
            $table->unsignedTinyInteger('day_of_week')->default(5);
            $table->time('start_time');
            $table->time('end_time');
            $table->decimal('entrance_fee', 10, 2)->default(0);
            $table->unsignedInteger('max_slots')->default(40);
            $table->unsignedTinyInteger('rounds')->default(3);
            $table->string('status', 20)->default('open'); // open, full, closed, completed, cancelled
            $table->boolean('matches_generated')->default(false);
            $table->foreignId('location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();

            $table->unique(['event_date', 'location_id']);
            $table->index('status');
            $table->index('event_date');
        });

        Schema::create('open_play_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('open_play_event_id')->constrained('open_play_events')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('slot_number');
            $table->string('status', 20)->default('registered'); // registered, checked_in, cancelled
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->timestamp('checked_in_at')->nullable();
            $table->timestamp('registered_at')->useCurrent();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['open_play_event_id', 'user_id']);
            $table->index(['open_play_event_id', 'status']);
        });

        Schema::create('open_play_rounds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('open_play_event_id')->constrained('open_play_events')->cascadeOnDelete();
            $table->unsignedInteger('round_number');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['open_play_event_id', 'round_number']);
        });

        Schema::create('open_play_matches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('open_play_round_id')->constrained('open_play_rounds')->cascadeOnDelete();
            $table->foreignId('open_play_event_id')->constrained('open_play_events')->cascadeOnDelete();
            $table->foreignId('court_id')->nullable()->constrained('courts')->nullOnDelete();
            $table->string('court_label', 100)->nullable();
            $table->foreignId('team1_player1_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('team1_player2_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('team2_player1_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('team2_player2_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index(['open_play_event_id', 'open_play_round_id']);
        });

        // Seed the singleton settings row with the requested Friday defaults.
        DB::table('open_play_settings')->insert([
            'is_enabled' => false,
            'day_of_week' => 5,
            'open_play_start' => '18:00:00',
            'open_play_end' => '22:00:00',
            'booking_open_start' => '05:00:00',
            'booking_open_end' => '17:00:00',
            'booking_resume_start' => '23:00:00',
            'booking_resume_end' => '00:00:00',
            'entrance_fee' => 150.00,
            'max_slots' => 40,
            'rounds' => 3,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('open_play_matches');
        Schema::dropIfExists('open_play_rounds');
        Schema::dropIfExists('open_play_registrations');
        Schema::dropIfExists('open_play_events');
        Schema::dropIfExists('open_play_settings');
    }
};
