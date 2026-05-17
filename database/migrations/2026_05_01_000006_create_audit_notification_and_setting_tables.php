<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 100);
            $table->string('entity_type', 100);
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('request_method', 10)->nullable();
            $table->string('request_url', 500)->nullable();
            $table->integer('response_status')->nullable();
            $table->integer('execution_time_ms')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['entity_type', 'entity_id']);
            $table->index('action');
            $table->index('created_at');
        });

        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reservation_id')->nullable()->constrained()->nullOnDelete();
            $table->enum('notification_type', ['email', 'sms', 'in_app', 'webhook']);
            $table->string('channel', 50);
            $table->string('recipient');
            $table->string('subject', 500)->nullable();
            $table->text('message')->nullable();
            $table->enum('status', ['pending', 'sent', 'failed', 'delivered', 'read'])->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index('status');
            $table->index('notification_type');
            $table->index('created_at');
        });

        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('setting_key', 100)->unique();
            $table->text('setting_value')->nullable();
            $table->enum('setting_type', ['string', 'integer', 'boolean', 'json', 'array'])->default('string');
            $table->string('group_name', 100)->default('general');
            $table->string('display_name', 200)->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_editable')->default(true);
            $table->boolean('is_public')->default(false);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index('group_name');
            $table->index('is_public');
        });

        DB::table('system_settings')->insert([
            ['setting_key' => 'system_name', 'setting_value' => 'Pickle Ballan ni Juan', 'setting_type' => 'string', 'group_name' => 'general', 'display_name' => 'System Name', 'created_at' => now(), 'updated_at' => now()],
            ['setting_key' => 'owner_gcash_number', 'setting_value' => '09123456789', 'setting_type' => 'string', 'group_name' => 'payment', 'display_name' => 'Owner GCash Number', 'created_at' => now(), 'updated_at' => now()],
            ['setting_key' => 'pending_payment_expiry_hours', 'setting_value' => '2', 'setting_type' => 'integer', 'group_name' => 'reservation', 'display_name' => 'Pending Payment Expiry (Hours)', 'created_at' => now(), 'updated_at' => now()],
            ['setting_key' => 'max_booking_advance_days', 'setting_value' => '30', 'setting_type' => 'integer', 'group_name' => 'reservation', 'display_name' => 'Maximum Advance Booking Days', 'created_at' => now(), 'updated_at' => now()],
            ['setting_key' => 'min_booking_notice_hours', 'setting_value' => '1', 'setting_type' => 'integer', 'group_name' => 'reservation', 'display_name' => 'Minimum Hours Before Booking', 'created_at' => now(), 'updated_at' => now()],
            ['setting_key' => 'buffer_between_bookings_minutes', 'setting_value' => '15', 'setting_type' => 'integer', 'group_name' => 'reservation', 'display_name' => 'Buffer Time Between Bookings', 'created_at' => now(), 'updated_at' => now()],
            ['setting_key' => 'enable_sms_notifications', 'setting_value' => 'true', 'setting_type' => 'boolean', 'group_name' => 'notifications', 'display_name' => 'Enable SMS Notifications', 'created_at' => now(), 'updated_at' => now()],
            ['setting_key' => 'enable_email_notifications', 'setting_value' => 'true', 'setting_type' => 'boolean', 'group_name' => 'notifications', 'display_name' => 'Enable Email Notifications', 'created_at' => now(), 'updated_at' => now()],
            ['setting_key' => 'timezone', 'setting_value' => 'Asia/Manila', 'setting_type' => 'string', 'group_name' => 'general', 'display_name' => 'System Timezone', 'created_at' => now(), 'updated_at' => now()],
            ['setting_key' => 'rating_admin_approval_required', 'setting_value' => 'false', 'setting_type' => 'boolean', 'group_name' => 'ratings', 'display_name' => 'Admin Approval Required for Ratings', 'created_at' => now(), 'updated_at' => now()],
        ]);

        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('template_code', 100)->unique();
            $table->string('template_name', 200);
            $table->enum('channel', ['email', 'sms', 'in_app']);
            $table->text('subject_template')->nullable();
            $table->text('body_template');
            $table->json('variables')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index('channel');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('notification_logs');
        Schema::dropIfExists('audit_logs');
    }
};
