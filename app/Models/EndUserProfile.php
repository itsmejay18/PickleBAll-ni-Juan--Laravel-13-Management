<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EndUserProfile extends Model
{
    protected $guarded = [];

    protected $casts = [
        'birth_date' => 'date',
        'notification_preferences' => 'array',
        'last_active_at' => 'datetime',
        'total_spent' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
