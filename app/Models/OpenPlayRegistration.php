<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpenPlayRegistration extends Model
{
    protected $guarded = [];

    protected $casts = [
        'slot_number' => 'integer',
        'amount_paid' => 'decimal:2',
        'checked_in_at' => 'datetime',
        'registered_at' => 'datetime',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(OpenPlayEvent::class, 'open_play_event_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function expirePending(): int
    {
        $timeoutMinutes = (int) \App\Models\SystemSetting::value('booking_timeout_minutes', '3');
        $threshold = now()->subMinutes($timeoutMinutes);

        $expired = static::query()
            ->where('status', 'pending_payment')
            ->where('registered_at', '<=', $threshold)
            ->get();

        $count = 0;
        foreach ($expired as $reg) {
            $reg->update(['status' => 'cancelled']);

            // Re-open event status if it was full
            $event = $reg->event;
            if ($event && $event->status === 'full' && ! $event->isFull()) {
                $event->update(['status' => 'open']);
            }
            $count++;
        }

        return $count;
    }
}
