<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('court_pricing_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('court_id')->constrained()->cascadeOnDelete();
            $table->string('rule_name', 100);
            $table->tinyInteger('day_of_week')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->boolean('is_holiday')->default(false);
            $table->boolean('is_peak_season')->default(false);
            $table->decimal('base_price', 10, 2);
            $table->decimal('peak_surcharge_percentage', 5, 2)->default(0);
            $table->integer('minimum_hours')->default(1);
            $table->integer('maximum_hours')->default(4);
            $table->integer('priority')->default(0);
            $table->boolean('is_active')->default(true);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index(['court_id', 'is_active']);
            $table->index(['effective_from', 'effective_to']);
            $table->index(['day_of_week', 'start_time', 'end_time']);
        });

        Schema::create('equipment_types', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id')->nullable();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->decimal('rental_price_per_unit', 10, 2);
            $table->decimal('deposit_amount', 10, 2)->default(0);
            $table->decimal('late_fee_per_hour', 10, 2)->default(0);
            $table->decimal('damage_replacement_cost', 10, 2)->nullable();
            $table->boolean('is_available_for_rent')->default(true);
            $table->boolean('requires_deposit')->default(false);
            $table->integer('max_rental_quantity_per_booking')->default(10);
            $table->integer('display_order')->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();

            $table->index('is_available_for_rent');
        });

        Schema::create('equipment_inventory', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->foreignId('equipment_type_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('total_quantity')->default(0);
            $table->unsignedInteger('available_quantity')->default(0);
            $table->unsignedInteger('reserved_quantity')->default(0);
            $table->unsignedInteger('damaged_quantity')->default(0);
            $table->unsignedInteger('lost_quantity')->default(0);
            $table->unsignedInteger('under_maintenance_quantity')->default(0);
            $table->timestamp('last_inventory_count_at')->nullable();
            $table->foreignId('last_inventory_count_by')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedInteger('reorder_point')->default(5);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['location_id', 'equipment_type_id']);
            $table->index('available_quantity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment_inventory');
        Schema::dropIfExists('equipment_types');
        Schema::dropIfExists('court_pricing_rules');
    }
};
