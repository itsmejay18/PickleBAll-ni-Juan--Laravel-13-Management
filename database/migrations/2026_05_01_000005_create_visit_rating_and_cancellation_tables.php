<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('check_in_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('checked_in_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('checked_in_at')->useCurrent();
            $table->enum('check_in_method', ['staff_search', 'manual'])->default('staff_search');
            $table->time('actual_start_time')->nullable();
            $table->boolean('customer_show_proof')->default(true);
            $table->string('verified_via', 50)->default('digital_receipt');
            $table->json('equipment_released')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('checked_in_by');
            $table->index('checked_in_at');
        });

        Schema::create('check_out_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('checked_out_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('checked_out_at')->useCurrent();
            $table->time('actual_end_time')->nullable();
            $table->json('equipment_returned')->nullable();
            $table->json('equipment_damaged')->nullable();
            $table->json('equipment_lost')->nullable();
            $table->decimal('damage_charges', 10, 2)->default(0);
            $table->decimal('late_charges', 10, 2)->default(0);
            $table->decimal('total_additional_charges', 10, 2)->default(0);
            $table->boolean('customer_paid_additional')->default(false);
            $table->foreignId('payment_collected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('feedback_from_customer')->nullable();
            $table->boolean('rating_reminder_sent')->default(false);
            $table->timestamp('created_at')->useCurrent();

            $table->index('checked_out_by');
        });

        Schema::create('ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('court_id')->constrained()->restrictOnDelete();
            $table->unsignedTinyInteger('rating_score');
            $table->string('review_title')->nullable();
            $table->text('review_comment')->nullable();
            $table->json('categories')->nullable();
            $table->boolean('would_recommend')->nullable();
            $table->boolean('is_verified_purchase')->default(true);
            $table->enum('status', ['pending', 'approved', 'rejected', 'hidden'])->default('pending');
            $table->integer('helpful_count')->default(0);
            $table->integer('not_helpful_count')->default(0);
            $table->text('admin_response')->nullable();
            $table->foreignId('admin_responded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('admin_responded_at')->nullable();
            $table->unsignedTinyInteger('original_rating_score')->nullable();
            $table->unsignedTinyInteger('admin_adjusted_score')->nullable();
            $table->text('admin_adjustment_reason')->nullable();
            $table->foreignId('admin_adjusted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('admin_adjusted_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();

            $table->index('user_id');
            $table->index('court_id');
            $table->index('rating_score');
            $table->index('status');
            $table->index('created_at');
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE ratings ADD CONSTRAINT chk_ratings_score CHECK (rating_score BETWEEN 1 AND 5)');
        }

        Schema::create('rating_helpful_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rating_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_helpful');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['user_id', 'rating_id']);
        });

        Schema::create('cancellation_policies', function (Blueprint $table) {
            $table->id();
            $table->string('policy_name', 100);
            $table->text('description')->nullable();
            $table->integer('hours_before_reservation');
            $table->decimal('refund_percentage', 5, 2);
            $table->boolean('is_active')->default(true);
            $table->enum('applies_to', ['online', 'walk_in', 'both'])->default('both');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index('is_active');
        });

        DB::table('cancellation_policies')->insert([
            ['policy_name' => 'Full Refund - 24+ hours', 'hours_before_reservation' => 24, 'refund_percentage' => 100, 'applies_to' => 'both', 'created_at' => now(), 'updated_at' => now()],
            ['policy_name' => '50% Refund - 12-24 hours', 'hours_before_reservation' => 12, 'refund_percentage' => 50, 'applies_to' => 'both', 'created_at' => now(), 'updated_at' => now()],
            ['policy_name' => 'No Refund - Less than 12 hours', 'hours_before_reservation' => 0, 'refund_percentage' => 0, 'applies_to' => 'both', 'created_at' => now(), 'updated_at' => now()],
        ]);

        Schema::create('cancellation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('cancelled_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('cancelled_at')->useCurrent();
            $table->foreignId('cancellation_policy_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('refund_amount', 10, 2)->nullable();
            $table->foreignId('refund_payment_id')->nullable()->constrained('payments')->nullOnDelete();
            $table->enum('reason_category', ['customer_request', 'no_show', 'payment_failed', 'staff_cancelled', 'system_auto', 'maintenance']);
            $table->text('reason_text');
            $table->boolean('customer_notified')->default(true);
            $table->timestamp('notification_sent_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('cancelled_by');
            $table->index('cancellation_policy_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cancellation_logs');
        Schema::dropIfExists('cancellation_policies');
        Schema::dropIfExists('rating_helpful_votes');
        Schema::dropIfExists('ratings');
        Schema::dropIfExists('check_out_logs');
        Schema::dropIfExists('check_in_logs');
    }
};
