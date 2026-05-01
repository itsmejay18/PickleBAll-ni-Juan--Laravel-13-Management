<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('locations', function (Blueprint $table) {
            $table->id();
            $table->string('name', 200);
            $table->string('slug', 200)->unique();
            $table->string('branch_code', 20)->unique();
            $table->string('address_line1', 500);
            $table->string('address_line2', 500)->nullable();
            $table->string('city', 100);
            $table->string('province', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('country', 100)->default('Philippines');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->text('google_maps_embed_url')->nullable();
            $table->string('whatsapp_number', 20)->nullable();
            $table->string('landline_number', 20)->nullable();
            $table->string('email_address')->nullable();
            $table->json('operating_hours');
            $table->string('timezone', 50)->default('Asia/Manila');
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->date('opening_date')->nullable();
            $table->string('featured_image_path', 500)->nullable();
            $table->json('gallery_images')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();

            $table->index('city');
            $table->index('is_active');
            $table->index('manager_id');
            $table->index(['latitude', 'longitude']);
        });

        Schema::create('end_user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('middle_name', 100)->nullable();
            $table->string('suffix', 20)->nullable();
            $table->date('birth_date')->nullable();
            $table->enum('gender', ['male', 'female', 'other', 'prefer_not_to_say'])->nullable();
            $table->string('profile_picture_path', 500)->nullable();
            $table->string('emergency_contact_name', 200)->nullable();
            $table->string('emergency_contact_number', 20)->nullable();
            $table->string('preferred_language', 10)->default('en');
            $table->json('notification_preferences')->nullable();
            $table->integer('total_bookings')->default(0);
            $table->decimal('total_spent', 12, 2)->default(0);
            $table->integer('loyalty_points')->default(0);
            $table->timestamp('last_active_at')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index(['first_name', 'last_name']);
            $table->index('total_bookings');
            $table->index('loyalty_points');
        });

        Schema::create('staff_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('middle_name', 100)->nullable();
            $table->string('employee_id', 50)->unique();
            $table->string('position', 100)->nullable();
            $table->date('hire_date');
            $table->date('termination_date')->nullable();
            $table->foreignId('assigned_location_id')->nullable()->constrained('locations')->nullOnDelete();
            $table->boolean('can_confirm_payments')->default(false);
            $table->boolean('can_process_refunds')->default(false);
            $table->boolean('can_manage_inventory')->default(false);
            $table->decimal('max_discount_percentage', 5, 2)->default(0);
            $table->decimal('hourly_rate', 10, 2)->nullable();
            $table->json('schedule_preferences')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();
        });

        Schema::create('admin_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->enum('admin_level', ['super', 'full', 'restricted'])->default('restricted');
            $table->json('permissions')->nullable();
            $table->boolean('two_factor_enabled')->default(false);
            $table->string('two_factor_secret')->nullable();
            $table->json('backup_codes')->nullable();
            $table->timestamp('last_password_change')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();

            $table->index('admin_level');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_profiles');
        Schema::dropIfExists('staff_profiles');
        Schema::dropIfExists('end_user_profiles');
        Schema::dropIfExists('locations');
    }
};
