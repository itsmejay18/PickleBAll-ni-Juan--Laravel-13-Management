<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CancellationPolicy extends Model
{
    protected $guarded = [];

    protected $casts = [
        'is_active' => 'boolean',
        'refund_percentage' => 'float',
    ];
}
