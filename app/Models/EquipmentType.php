<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EquipmentType extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'is_available_for_rent' => 'boolean',
        'requires_deposit' => 'boolean',
        'rental_price_per_unit' => 'float',
        'deposit_amount' => 'float',
        'late_fee_per_hour' => 'float',
        'damage_replacement_cost' => 'float',
    ];

    public function inventory(): HasMany
    {
        return $this->hasMany(EquipmentInventory::class);
    }
}
