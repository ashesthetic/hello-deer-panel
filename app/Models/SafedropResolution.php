<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SafedropResolution extends Model
{
    use HasFactory;

    protected $fillable = [
        'daily_sale_id',
        'bank_account_id',
        'user_id',
        'amount',
        'type',
        'notes'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    /**
     * Relationship with daily sale
     */
    public function dailySale()
    {
        return $this->belongsTo(DailySale::class);
    }

    /**
     * Relationship with bank account
     */
    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    /**
     * Relationship with user who resolved it
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if user can view this resolution
     */
    public function canBeViewedBy(User $user): bool
    {
        // Admins can view all resolutions
        if ($user->isAdmin()) {
            return true;
        }

        // Users can view resolutions they created
        return $this->user_id === $user->id;
    }

    /**
     * Check if user can delete this resolution
     */
    public function canBeDeletedBy(User $user): bool
    {
        // Only admins can delete resolutions
        return $user->isAdmin();
    }
}
