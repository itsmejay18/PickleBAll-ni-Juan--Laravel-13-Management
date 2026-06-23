<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OpenPlayRound extends Model
{
    protected $guarded = [];

    protected $casts = [
        'round_number' => 'integer',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(OpenPlayEvent::class, 'open_play_event_id');
    }

    public function matches(): HasMany
    {
        return $this->hasMany(OpenPlayMatch::class)->orderBy('id');
    }
}
