<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['email', 'mobile_number', 'photo_path', 'password', 'is_active', 'rating', 'wins', 'losses', 'matches_played'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    public const ROLE_SUPER_ADMIN = 'super_admin';

    public const ROLE_ADMIN = 'admin';

    public const ROLE_LOCATION_MANAGER = 'location_manager';

    public const ROLE_STAFF = 'staff';

    public const ROLE_END_USER = 'end_user';

    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable, SoftDeletes;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'mobile_verified_at' => 'datetime',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'locked_until' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // I4 fix: relationship so the layout can use $user->endUserProfile instead of a raw query
    public function endUserProfile(): HasOne
    {
        return $this->hasOne(EndUserProfile::class);
    }

    public function staffProfile(): HasOne
    {
        return $this->hasOne(StaffProfile::class);
    }

    public function adminProfile(): HasOne
    {
        return $this->hasOne(AdminProfile::class);
    }

    public function getWinRateAttribute(): float
    {
        return $this->matches_played > 0 
            ? round(($this->wins / $this->matches_played) * 100, 1) 
            : 0.0;
    }

    public function getBadgeNameAttribute(): string
    {
        $r = $this->rating;
        return match (true) {
            $r >= 1300 => 'Grandmaster',
            $r >= 1200 => 'Elite',
            $r >= 1100 => 'Pro',
            $r >= 1050 => 'Advanced',
            $r >= 1000 => 'Intermediate',
            default => 'Beginner',
        };
    }

    public function getBadgeEmojiAttribute(): string
    {
        $r = $this->rating;
        return match (true) {
            $r >= 1300 => '👑',
            $r >= 1200 => '💎',
            $r >= 1100 => '🔥',
            $r >= 1050 => '⭐',
            $r >= 1000 => '🥈',
            default => '🥉',
        };
    }
}
