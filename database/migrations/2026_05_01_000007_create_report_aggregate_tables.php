<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_reports_aggregates', function (Blueprint $table) {
            $table->id();
            $table->date('report_date');
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->integer('total_reservations')->default(0);
            $table->integer('total_online_reservations')->default(0);
            $table->integer('total_walkin_reservations')->default(0);
            $table->integer('total_cancellations')->default(0);
            $table->integer('total_no_shows')->default(0);
            $table->decimal('total_revenue', 12, 2)->default(0);
            $table->decimal('total_gcash_revenue', 12, 2)->default(0);
            $table->decimal('total_cash_revenue', 12, 2)->default(0);
            $table->integer('total_equipment_rented_rackets')->default(0);
            $table->integer('total_equipment_rented_balls')->default(0);
            $table->decimal('average_rating', 3, 2)->nullable();
            $table->time('peak_hour_start')->nullable();
            $table->time('peak_hour_end')->nullable();
            $table->decimal('utilization_rate', 5, 2)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['report_date', 'location_id']);
            $table->index('location_id');
            $table->index('report_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_reports_aggregates');
    }
};
