<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class StaffProfile extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'hire_date' => 'date',
        'termination_date' => 'date',
        'can_confirm_payments' => 'boolean',
        'can_process_refunds' => 'boolean',
        'can_manage_inventory' => 'boolean',
        'max_discount_percentage' => 'float',
        'hourly_rate' => 'float',
        'schedule_preferences' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'assigned_location_id');
    }
}
