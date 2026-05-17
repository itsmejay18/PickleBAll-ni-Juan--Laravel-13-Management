<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourtPricingRule extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_holiday' => 'boolean',
        'is_peak_season' => 'boolean',
        'is_active' => 'boolean',
        'effective_from' => 'date',
        'effective_to' => 'date',
        'base_price' => 'float',
        'peak_surcharge_percentage' => 'float',
    ];

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }
}
