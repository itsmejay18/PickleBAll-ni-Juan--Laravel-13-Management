<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Court extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'has_lighting' => 'boolean',
        'has_net' => 'boolean',
        'has_seating' => 'boolean',
        'has_shade' => 'boolean',
        'is_airconditioned' => 'boolean',
        'is_active' => 'boolean',
        'width' => 'float',
        'length' => 'float',
    ];

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(Reservation::class);
    }

    public function pricingRules(): HasMany
    {
        return $this->hasMany(CourtPricingRule::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(CourtSchedule::class);
    }

    public function maintenance(): HasMany
    {
        return $this->hasMany(CourtMaintenance::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(CourtImage::class);
    }
}
