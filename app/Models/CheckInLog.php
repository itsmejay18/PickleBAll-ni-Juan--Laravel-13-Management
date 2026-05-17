<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CheckInLog extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'checked_in_at' => 'datetime',
        'created_at' => 'datetime',
        'customer_show_proof' => 'boolean',
        'equipment_released' => 'array',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }
}
