<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourtSchedule extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_available' => 'boolean',
        'effective_from' => 'date',
        'effective_to' => 'date',
    ];

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }
}
