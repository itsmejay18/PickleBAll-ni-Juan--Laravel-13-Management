<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Reservation extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'reservation_date' => 'date',
        'court_price_per_hour' => 'float',
        'court_subtotal' => 'float',
        'equipment_total' => 'float',
        'discount_amount' => 'float',
        'tax_amount' => 'float',
        'tax_rate' => 'float',
        'grand_total' => 'float',
        'is_active' => 'boolean',
        'expires_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'reschedule_locked' => 'boolean',
        'reschedule_locked_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function equipment(): HasMany
    {
        return $this->hasMany(ReservationEquipment::class);
    }

    public function checkInLog(): HasOne
    {
        return $this->hasOne(CheckInLog::class);
    }

    public function checkOutLog(): HasOne
    {
        return $this->hasOne(CheckOutLog::class);
    }

    public function rating(): HasOne
    {
        return $this->hasOne(Rating::class);
    }

    public function cancellationLog(): HasOne
    {
        return $this->hasOne(CancellationLog::class);
    }

    public function rescheduleLockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reschedule_locked_by');
    }
}
