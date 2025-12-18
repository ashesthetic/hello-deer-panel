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
        'total_contributions',
        'total_withdrawals',
        'total_distributions',
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
        return $this->equityTransactions()
            ->selectRaw('SUM(CASE WHEN transaction_type IN ("contribution", "distribution") THEN amount ELSE -amount END) as total')
            ->value('total') ?? 0.0;
    }

    /**
     * Get the total contributions for this owner
     */
    public function getTotalContributionsAttribute(): float
    {
        return $this->equityTransactions()
            ->where('transaction_type', 'contribution')
            ->sum('amount');
    }

    /**
     * Get the total withdrawals for this owner
     */
    public function getTotalWithdrawalsAttribute(): float
    {
        return $this->equityTransactions()
            ->where('transaction_type', 'withdrawal')
            ->sum('amount');
    }

    /**
     * Get the total distributions for this owner
     */
    public function getTotalDistributionsAttribute(): float
    {
        return $this->equityTransactions()
            ->where('transaction_type', 'distribution')
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
