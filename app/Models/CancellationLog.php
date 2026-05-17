<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CancellationLog extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected $casts = [
        'cancelled_at' => 'datetime',
        'notification_sent_at' => 'datetime',
        'created_at' => 'datetime',
        'refund_amount' => 'float',
        'customer_notified' => 'boolean',
    ];

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }

    public function policy(): BelongsTo
    {
        return $this->belongsTo(CancellationPolicy::class, 'cancellation_policy_id');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }
}
