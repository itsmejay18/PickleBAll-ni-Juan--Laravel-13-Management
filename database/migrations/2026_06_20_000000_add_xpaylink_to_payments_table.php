<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('xpaylink_session_id', 100)->nullable()->unique()->after('gcash_screenshot_path');
        });

        DB::table('system_settings')->insert([
            [
                'setting_key' => 'xpaylink_enabled',
                'setting_value' => 'true',
                'setting_type' => 'boolean',
                'group_name' => 'payment',
                'display_name' => 'Enable XPayLink Automatic Payments',
                'description' => 'Toggles between XPayLink automated payment integration and manual QR/screenshot confirmations.',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'setting_key' => 'xpaylink_public_key',
                'setting_value' => '',
                'setting_type' => 'string',
                'group_name' => 'payment',
                'display_name' => 'XPayLink Public Key',
                'description' => 'The merchant public key (pk_live_...) from XPayLink dashboard settings.',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'setting_key' => 'xpaylink_secret_key',
                'setting_value' => '',
                'setting_type' => 'string',
                'group_name' => 'payment',
                'display_name' => 'XPayLink Secret Key',
                'description' => 'The merchant secret key (sk_live_...) from XPayLink dashboard settings.',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'setting_key' => 'xpaylink_endpoint',
                'setting_value' => 'https://synthwave.space/api/create-session.php',
                'setting_type' => 'string',
                'group_name' => 'payment',
                'display_name' => 'XPayLink Session API Endpoint',
                'description' => 'The API URL used to initiate payment sessions.',
                'created_at' => now(),
                'updated_at' => now()
            ],
        ]);
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('xpaylink_session_id');
        });

        DB::table('system_settings')
            ->whereIn('setting_key', [
                'xpaylink_enabled',
                'xpaylink_public_key',
                'xpaylink_secret_key',
                'xpaylink_endpoint'
            ])
            ->delete();
    }
};
