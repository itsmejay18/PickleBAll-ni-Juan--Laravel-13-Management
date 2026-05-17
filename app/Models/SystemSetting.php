<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SystemSetting extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_editable' => 'boolean',
        'is_public' => 'boolean',
    ];

    public static function value(string $key, mixed $default = null): mixed
    {
        return Cache::remember('system_setting:'.$key, 60, function () use ($key, $default) {
            $row = static::query()->where('setting_key', $key)->first();

            if (! $row) {
                return $default;
            }

            return match ($row->setting_type) {
                'integer' => (int) $row->setting_value,
                'boolean' => filter_var($row->setting_value, FILTER_VALIDATE_BOOLEAN),
                'json', 'array' => json_decode((string) $row->setting_value, true),
                default => $row->setting_value,
            };
        });
    }

    public static function set(string $key, mixed $value): void
    {
        $setting = static::query()->firstOrNew(['setting_key' => $key]);
        $setting->setting_value = is_scalar($value) ? (string) $value : json_encode($value);
        $setting->save();
        Cache::forget('system_setting:'.$key);
    }
}
