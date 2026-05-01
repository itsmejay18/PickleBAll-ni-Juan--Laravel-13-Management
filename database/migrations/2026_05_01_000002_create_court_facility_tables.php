<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('location_id')->constrained()->cascadeOnDelete();
            $table->string('court_number', 50);
            $table->string('court_name', 200)->nullable();
            $table->enum('court_type', ['indoor', 'outdoor', 'covered'])->default('indoor');
            $table->enum('surface_type', ['concrete', 'asphalt', 'acrylic', 'grass', 'clay'])->default('acrylic');
            $table->decimal('width', 5, 2)->nullable()->comment('Width in meters');
            $table->decimal('length', 5, 2)->nullable()->comment('Length in meters');
            $table->boolean('has_lighting')->default(true);
            $table->boolean('has_net')->default(true);
            $table->boolean('has_seating')->default(true);
            $table->boolean('has_shade')->default(false);
            $table->boolean('is_airconditioned')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('display_order')->default(0);
            $table->text('description')->nullable();
            $table->text('special_instructions')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();

            $table->unique(['location_id', 'court_number']);
            $table->index('is_active');
            $table->index('court_type');
        });

        Schema::create('court_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('court_id')->constrained()->cascadeOnDelete();
            $table->string('image_path', 500);
            $table->string('image_filename');
            $table->unsignedInteger('image_size')->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->string('alt_text')->nullable();
            $table->text('caption')->nullable();
            $table->boolean('is_primary')->default(false);
            $table->integer('display_order')->default(0);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
            $table->softDeletes();

            $table->index('is_primary');
        });

        Schema::create('court_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('court_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week')->comment('0=Sunday, 1=Monday...6=Saturday');
            $table->time('open_time');
            $table->time('close_time');
            $table->time('break_start_time')->nullable();
            $table->time('break_end_time')->nullable();
            $table->boolean('is_available')->default(true);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index(['court_id', 'day_of_week']);
            $table->index(['effective_from', 'effective_to']);
        });

        Schema::create('court_maintenance', function (Blueprint $table) {
            $table->id();
            $table->foreignId('court_id')->constrained()->cascadeOnDelete();
            $table->enum('maintenance_type', ['regular', 'emergency', 'tournament', 'private_event', 'holiday'])->default('regular');
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamp('start_datetime');
            $table->timestamp('end_datetime');
            $table->boolean('is_all_day')->default(false);
            $table->boolean('recurring_weekly')->default(false);
            $table->date('recurring_end_date')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->index(['court_id', 'start_datetime', 'end_datetime']);
            $table->index('maintenance_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('court_maintenance');
        Schema::dropIfExists('court_schedules');
        Schema::dropIfExists('court_images');
        Schema::dropIfExists('courts');
    }
};
