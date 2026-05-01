<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $isSqlite = DB::getDriverName() === 'sqlite';

        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('reservation_code', 50)->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('court_id')->constrained()->restrictOnDelete();
            $table->foreignId('location_id')->constrained()->restrictOnDelete();
            $table->date('reservation_date');
            $table->time('start_time');
            $table->time('end_time');
            if (DB::getDriverName() === 'sqlite') {
                $table->decimal('total_hours', 4, 2)->default(0);
            } else {
                $table->decimal('total_hours', 4, 2)->storedAs("timestampdiff(MINUTE, concat(reservation_date, ' ', start_time), concat(reservation_date, ' ', end_time)) / 60");
            }
            $table->decimal('court_price_per_hour', 10, 2);
            $table->decimal('court_subtotal', 10, 2);
            $table->decimal('equipment_total', 10, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->enum('discount_type', ['percentage', 'fixed'])->nullable();
            $table->string('discount_reason')->nullable();
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->decimal('grand_total', 10, 2);
            $table->enum('reservation_type', ['online', 'walk_in']);
            $table->enum('status', ['pending_payment', 'payment_verification', 'confirmed', 'checked_in', 'ongoing', 'completed', 'cancelled', 'no_show', 'refunded'])->default('pending_payment');
            $table->enum('payment_status', ['unpaid', 'pending_verification', 'paid', 'partially_paid', 'refunded'])->default('unpaid');
            $table->text('special_requests')->nullable();
            $table->boolean('is_active')->default(true);
            if (DB::getDriverName() === 'sqlite') {
                $table->timestamp('expires_at')->nullable();
            } else {
                $table->timestamp('expires_at')->virtualAs('date_add(created_at, interval 2 hour)');
            }
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('created_ip', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();

            $table->index(['user_id', 'reservation_date']);
            $table->index(['court_id', 'reservation_date', 'start_time', 'end_time']);
            $table->index(['location_id', 'reservation_date']);
            $table->index('status');
            $table->index('payment_status');
            $table->index('expires_at');
            $table->index('created_at');
        });

        if (! $isSqlite) {
            DB::statement('ALTER TABLE reservations ADD CONSTRAINT chk_reservations_end_after_start CHECK (end_time > start_time)');
        }

        Schema::create('reservation_equipment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('equipment_type_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity');
            $table->decimal('price_per_unit', 10, 2);
            if (DB::getDriverName() === 'sqlite') {
                $table->decimal('subtotal', 10, 2)->default(0);
            } else {
                $table->decimal('subtotal', 10, 2)->storedAs('quantity * price_per_unit');
            }
            $table->decimal('deposit_charged', 10, 2)->default(0);
            $table->boolean('deposit_returned')->default(false);
            $table->timestamp('deposit_returned_at')->nullable();
            $table->boolean('is_returned')->default(false);
            $table->timestamp('returned_at')->nullable();
            $table->foreignId('returned_to_staff_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('damage_notes')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['reservation_id', 'equipment_type_id']);
            $table->index('is_returned');
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->string('payment_reference', 100)->unique();
            $table->decimal('amount', 10, 2);
            $table->enum('payment_method', ['gcash', 'cash', 'bank_transfer', 'credit_card']);
            $table->enum('payment_type', ['full', 'partial', 'deposit'])->default('full');
            $table->string('gcash_number_sent_to', 20)->nullable();
            $table->string('gcash_sender_number', 20)->nullable();
            $table->string('gcash_reference_number', 100)->nullable();
            $table->string('gcash_screenshot_path', 500)->nullable();
            $table->decimal('cash_received_amount', 10, 2)->nullable();
            $table->decimal('cash_change_amount', 10, 2)->nullable();
            $table->enum('status', ['pending', 'verified', 'rejected', 'refunded'])->default('pending');
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->foreignId('refunded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('refund_reason')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index('gcash_reference_number');
            $table->index('status');
            $table->index('verified_by');
            $table->index('created_at');
        });

        Schema::create('gcash_transactions_log', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('owner_gcash_number', 20);
            $table->string('user_sent_from_number', 20)->nullable();
            $table->string('reference_number', 100);
            $table->string('screenshot_path', 500)->nullable();
            $table->decimal('amount', 10, 2);
            $table->enum('status', ['pending_verification', 'verified', 'rejected', 'disputed'])->default('pending_verification');
            $table->text('verification_notes')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->text('dispute_reason')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index('reference_number');
            $table->index('status');
            $table->index('created_at');
        });

        Schema::create('inventory_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->foreignId('equipment_type_id')->constrained()->restrictOnDelete();
            $table->foreignId('reservation_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('transaction_type', ['check_out', 'check_in', 'damaged', 'lost', 'maintenance', 'restock', 'count_adjustment']);
            $table->integer('quantity');
            $table->integer('previous_available');
            $table->integer('new_available');
            $table->text('notes')->nullable();
            $table->foreignId('performed_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['location_id', 'equipment_type_id']);
            $table->index('reservation_id');
            $table->index('transaction_type');
            $table->index('performed_by');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_transactions');
        Schema::dropIfExists('gcash_transactions_log');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('reservation_equipment');
        Schema::dropIfExists('reservations');
    }
};
