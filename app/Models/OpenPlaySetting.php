<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OpenPlaySetting extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_enabled' => 'boolean',
        'day_of_week' => 'integer',
        'entrance_fee' => 'decimal:2',
        'max_slots' => 'integer',
        'rounds' => 'integer',
    ];

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'is_enabled' => false,
            'day_of_week' => 5,
            'open_play_start' => '18:00:00',
            'open_play_end' => '22:00:00',
            'attendance_closing_time' => '17:55:00',
            'booking_open_start' => '05:00:00',
            'booking_open_end' => '17:00:00',
            'booking_resume_start' => '23:00:00',
            'booking_resume_end' => '00:00:00',
            'entrance_fee' => 150.00,
            'max_slots' => 40,
            'rounds' => 3,
        ]);
    }
}
