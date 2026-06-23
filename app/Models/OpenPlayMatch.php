<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpenPlayMatch extends Model
{
    protected $guarded = [];

    public function round(): BelongsTo
    {
        return $this->belongsTo(OpenPlayRound::class, 'open_play_round_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(OpenPlayEvent::class, 'open_play_event_id');
    }

    public function court(): BelongsTo
    {
        return $this->belongsTo(Court::class);
    }

    public function team1Player1(): BelongsTo
    {
        return $this->belongsTo(User::class, 'team1_player1_id');
    }

    public function team1Player2(): BelongsTo
    {
        return $this->belongsTo(User::class, 'team1_player2_id');
    }

    public function team2Player1(): BelongsTo
    {
        return $this->belongsTo(User::class, 'team2_player1_id');
    }

    public function team2Player2(): BelongsTo
    {
        return $this->belongsTo(User::class, 'team2_player2_id');
    }

    /** @return array<int> */
    public function playerIds(): array
    {
        return array_values(array_filter([
            $this->team1_player1_id,
            $this->team1_player2_id,
            $this->team2_player1_id,
            $this->team2_player2_id,
        ]));
    }
}
