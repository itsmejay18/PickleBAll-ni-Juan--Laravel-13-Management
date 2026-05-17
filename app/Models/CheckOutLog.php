<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheckOutLog extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'checked_out_at' => 'datetime',
        'created_at' => 'datetime',
        'equipment_returned' => 'array',
        'equipment_damaged' => 'array',
        'equipment_lost' => 'array',
        'damage_charges' => 'float',
        'late_charges' => 'float',
        'total_additional_charges' => 'float',
        'customer_paid_additional' => 'boolean',
        'rating_reminder_sent' => 'boolean',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_out_by');
    }
}
