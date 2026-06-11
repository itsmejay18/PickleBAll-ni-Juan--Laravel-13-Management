<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->boolean('reschedule_locked')->default(false)->after('special_requests');
            $table->text('reschedule_locked_reason')->nullable()->after('reschedule_locked');
            $table->foreignId('reschedule_locked_by')->nullable()->constrained('users')->nullOnDelete()->after('reschedule_locked_reason');
            $table->timestamp('reschedule_locked_at')->nullable()->after('reschedule_locked_by');
            $table->index('reschedule_locked');
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropForeign(['reschedule_locked_by']);
            $table->dropIndex(['reschedule_locked']);
            $table->dropColumn(['reschedule_locked', 'reschedule_locked_reason', 'reschedule_locked_by', 'reschedule_locked_at']);
        });
    }
};
