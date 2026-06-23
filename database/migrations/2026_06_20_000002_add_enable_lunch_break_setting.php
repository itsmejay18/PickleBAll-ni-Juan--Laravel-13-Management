<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('system_settings')->updateOrInsert(
            ['setting_key' => 'enable_lunch_break'],
            [
                'setting_value' => 'true',
                'setting_type' => 'boolean',
                'group_name' => 'general',
                'display_name' => 'Enable Lunch Break',
                'description' => 'Enable standard lunch break from 12:00 PM to 12:30 PM across all courts.',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('system_settings')->where('setting_key', 'enable_lunch_break')->delete();
    }
};
