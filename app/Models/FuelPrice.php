<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FuelPrice extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'regular_87',
        'midgrade_91',
        'premium_94',
        'diesel',
        'user_id',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'regular_87' => 'decimal:3',
        'midgrade_91' => 'decimal:3',
        'premium_94' => 'decimal:3',
        'diesel' => 'decimal:3',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the user that created this fuel price entry.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to filter by user based on their role
     */
    public function scopeByUser($query, $user)
    {
        if ($user->isEditor()) {
            return $query->where('user_id', $user->id);
        }
        
        return $query; // Admin can see all
    }
}
