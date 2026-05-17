<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationEquipment extends Model
{
    protected $table = 'reservation_equipment';

    protected $guarded = [];

    protected $casts = [
        'price_per_unit' => 'float',
        'subtotal' => 'float',
        'deposit_charged' => 'float',
        'deposit_returned' => 'boolean',
        'deposit_returned_at' => 'datetime',
        'is_returned' => 'boolean',
        'returned_at' => 'datetime',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function equipmentType(): BelongsTo
    {
        return $this->belongsTo(EquipmentType::class);
    }
}
