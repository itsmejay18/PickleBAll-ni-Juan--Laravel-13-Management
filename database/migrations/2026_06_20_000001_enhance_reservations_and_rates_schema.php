<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Modify reservations table
        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('reservations', function (Blueprint $table) {
                $table->dropColumn('expires_at');
            });
            Schema::table('reservations', function (Blueprint $table) {
                $table->timestamp('expires_at')->nullable()->after('is_active')->index();
            });
        }

        Schema::table('reservations', function (Blueprint $table) {
            $table->timestamp('terms_accepted_at')->nullable()->after('expires_at');
        });

        // 2. Insert default terms & conditions setting
        DB::table('system_settings')->updateOrInsert(
            ['setting_key' => 'booking_terms_and_conditions'],
            [
                'setting_value' => "### Terms & Conditions\n\n1. **No cancellation once a booking is confirmed.**\n2. **The GCash checkout session is valid for 3 minutes only.**\n3. **Booking will be automatically cancelled if payment is not completed within 3 minutes.**",
                'setting_type' => 'string',
                'group_name' => 'reservation',
                'display_name' => 'Booking Terms and Conditions',
                'description' => 'Starter template for the customer Terms & Conditions agreement popup.',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        // 1. Remove setting
        DB::table('system_settings')->where('setting_key', 'booking_terms_and_conditions')->delete();

        // 2. Remove columns
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn('terms_accepted_at');
        });

        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('reservations', function (Blueprint $table) {
                $table->dropColumn('expires_at');
            });
            Schema::table('reservations', function (Blueprint $table) {
                $table->timestamp('expires_at')->virtualAs('date_add(created_at, interval 2 hour)')->after('is_active')->index();
            });
        }
    }
};
