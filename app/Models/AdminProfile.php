<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class AdminProfile extends Model
{
    use SoftDeletes;

    protected $guarded = [];

    protected $casts = [
        'permissions' => 'array',
        'backup_codes' => 'array',
        'two_factor_enabled' => 'boolean',
        'last_password_change' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
