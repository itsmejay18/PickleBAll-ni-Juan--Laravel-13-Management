<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OpenPlayEvent extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'event_date' => 'date',
        'entrance_fee' => 'decimal:2',
        'max_slots' => 'integer',
        'rounds' => 'integer',
        'matches_generated' => 'boolean',
    ];

    public function registrations(): HasMany
    {
        return $this->hasMany(OpenPlayRegistration::class);
    }

    public function activeRegistrations(): HasMany
    {
        return $this->hasMany(OpenPlayRegistration::class)->where('status', '!=', 'cancelled');
    }

    public function rounds(): HasMany
    {
        return $this->hasMany(OpenPlayRound::class)->orderBy('round_number');
    }

    public function matches(): HasMany
    {
        return $this->hasMany(OpenPlayMatch::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function takenSlots(): int
    {
        return $this->activeRegistrations()->count();
    }

    public function remainingSlots(): int
    {
        return max(0, $this->max_slots - $this->takenSlots());
    }

    public function isFull(): bool
    {
        return $this->remainingSlots() <= 0;
    }

    public function isOpenForRegistration(): bool
    {
        return in_array($this->status, ['open'], true) && ! $this->isFull();
    }

    /**
     * Resolve (and lazily create) the upcoming Open Play event from settings.
     * Returns null when Open Play is disabled.
     */
    public static function upcoming(?OpenPlaySetting $settings = null): ?self
    {
        OpenPlayRegistration::expirePending();

        $settings = $settings ?: OpenPlaySetting::current();

        if (! $settings->is_enabled) {
            return null;
        }

        $date = static::nextOccurrence((int) $settings->day_of_week);

        $event = static::query()
            ->whereDate('event_date', $date->toDateString())
            ->where('location_id', $settings->location_id)
            ->first();

        if (! $event) {
            $event = static::create([
                'event_date' => $date->toDateString(),
                'location_id' => $settings->location_id,
                'day_of_week' => $settings->day_of_week,
                'start_time' => $settings->open_play_start,
                'end_time' => $settings->open_play_end,
                'attendance_closing_time' => $settings->attendance_closing_time,
                'entrance_fee' => $settings->entrance_fee,
                'max_slots' => $settings->max_slots,
                'rounds' => $settings->rounds,
                'status' => 'open',
            ]);
        }

        // Keep the live event in sync with the latest settings until matches are generated.
        if (! $event->matches_generated && in_array($event->status, ['open', 'full'], true)) {
            $event->update([
                'start_time' => $settings->open_play_start,
                'end_time' => $settings->open_play_end,
                'attendance_closing_time' => $settings->attendance_closing_time,
                'entrance_fee' => $settings->entrance_fee,
                'max_slots' => $settings->max_slots,
                'rounds' => $settings->rounds,
                'day_of_week' => $settings->day_of_week,
                'status' => $event->isFull() ? 'full' : 'open',
            ]);
        }

        return $event->fresh();
    }

    public static function nextOccurrence(int $dayOfWeek): Carbon
    {
        $today = Carbon::today();
        $diff = ($dayOfWeek - $today->dayOfWeek + 7) % 7;

        return $today->copy()->addDays($diff);
    }

    public function isAttendanceLocked(): bool
    {
        $closingDatetime = Carbon::parse($this->event_date->toDateString() . ' ' . $this->attendance_closing_time);
        return now()->greaterThanOrEqualTo($closingDatetime);
    }

    public function attendanceRemainingSeconds(): int
    {
        $closingDatetime = Carbon::parse($this->event_date->toDateString() . ' ' . $this->attendance_closing_time);
        return max(0, now()->diffInSeconds($closingDatetime, false));
    }

    public function waitingPlayersForRound(OpenPlayRound $round)
    {
        $presentUserIds = $this->activeRegistrations()
            ->where('status', 'checked_in')
            ->pluck('user_id')
            ->all();

        $playingUserIds = [];
        foreach ($round->matches as $match) {
            $playingUserIds = array_merge($playingUserIds, $match->playerIds());
        }

        $waitingUserIds = array_diff($presentUserIds, $playingUserIds);
        return User::whereIn('id', $waitingUserIds)->get();
    }

    public function initialWaitingQueue(int $courtCount)
    {
        $present = $this->activeRegistrations()
            ->where('status', 'checked_in')
            ->orderBy('checked_in_at')
            ->get();

        $activeCapacity = $courtCount * 4;
        if ($present->count() <= $activeCapacity) {
            return collect();
        }

        return $present->slice($activeCapacity);
    }
}
