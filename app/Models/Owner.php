<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Owner extends Model
{
    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'city',
        'province',
        'postal_code',
        'country',
        'ownership_percentage',
        'notes',
        'is_active',
        'user_id',
    ];

    protected $casts = [
        'ownership_percentage' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * The accessors to append to the model's array form.
     */
    protected $appends = [
        'total_equity',
        'total_investments',
        'total_withdrawals',
    ];

    /**
     * Get the user who created this owner record
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all equity transactions for this owner
     */
    public function equityTransactions(): HasMany
    {
        return $this->hasMany(OwnerEquity::class);
    }

    /**
     * Get the total equity balance for this owner
     */
    public function getTotalEquityAttribute(): float
    {
        $investments = $this->equityTransactions()->where('type', 'investment')->sum('amount');
        $withdrawals = $this->equityTransactions()->where('type', 'withdrawal')->sum('amount');
        return $investments - $withdrawals;
    }

    /**
     * Get the total investments for this owner
     */
    public function getTotalInvestmentsAttribute(): float
    {
        return $this->equityTransactions()
            ->where('type', 'investment')
            ->sum('amount');
    }

    /**
     * Get the total withdrawals for this owner
     */
    public function getTotalWithdrawalsAttribute(): float
    {
        return $this->equityTransactions()
            ->where('type', 'withdrawal')
            ->sum('amount');
    }

    /**
     * Scope to get only active owners
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get owners by user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }
}
