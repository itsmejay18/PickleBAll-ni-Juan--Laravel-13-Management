<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_role_types', function (Blueprint $table) {
            $table->unsignedTinyInteger('id', true);
            $table->string('role_name', 50)->unique();
            $table->string('role_slug', 50)->unique();
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('priority_level')->default(0)->comment('Higher = more permissions');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();
        });

        DB::table('user_role_types')->insert([
            [
                'role_name' => 'Super Admin',
                'role_slug' => 'super_admin',
                'description' => 'System owner with unrestricted platform access.',
                'priority_level' => 100,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'role_name' => 'Admin',
                'role_slug' => 'admin',
                'description' => 'Administrator with business management access.',
                'priority_level' => 80,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'role_name' => 'Location Manager',
                'role_slug' => 'location_manager',
                'description' => 'Manager for assigned branch operations.',
                'priority_level' => 60,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'role_name' => 'Staff',
                'role_slug' => 'staff',
                'description' => 'Facility staff who can manage daily operations.',
                'priority_level' => 40,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'role_name' => 'End User',
                'role_slug' => 'end_user',
                'description' => 'Customer account for booking courts and equipment.',
                'priority_level' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        Schema::create('user_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('role_id');
            $table->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent()->useCurrentOnUpdate();

            $table->unique(['user_id', 'role_id']);
            $table->index('is_active');
            $table->foreign('role_id')->references('id')->on('user_role_types')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('user_role_types');
    }
};
