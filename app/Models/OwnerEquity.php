<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Utils\TimezoneUtil;

class OwnerEquity extends Model
{
    protected $fillable = [
        'owner_id',
        'transaction_type',
        'amount',
        'transaction_date',
        'reference_number',
        'payment_method',
        'description',
        'notes',
        'user_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
    ];

    /**
     * The accessors to append to the model's array form.
     */
    protected $appends = [
        'formatted_transaction_date',
        'formatted_amount',
        'transaction_type_display',
        'is_positive',
        'is_negative',
    ];

    /**
     * Get the owner for this equity transaction
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
    }

    /**
     * Get the user who created this equity transaction
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to get transactions by owner
     */
    public function scopeByOwner($query, $ownerId)
    {
        return $query->where('owner_id', $ownerId);
    }

    /**
     * Scope to get transactions by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('transaction_type', $type);
    }

    /**
     * Scope to get transactions by date range
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    /**
     * Scope to get transactions by user
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Get the formatted transaction date in Alberta timezone
     */
    public function getFormattedTransactionDateAttribute(): string
    {
        return TimezoneUtil::formatDate($this->transaction_date);
    }

    /**
     * Get the formatted amount with currency
     */
    public function getFormattedAmountAttribute(): string
    {
        return '$' . number_format($this->amount, 2);
    }

    /**
     * Get the transaction type display name
     */
    public function getTransactionTypeDisplayAttribute(): string
    {
        return ucfirst($this->transaction_type);
    }

    /**
     * Check if this is a positive transaction (contribution or distribution)
     */
    public function getIsPositiveAttribute(): bool
    {
        return in_array($this->transaction_type, ['contribution', 'distribution']);
    }

    /**
     * Check if this is a negative transaction (withdrawal or adjustment)
     */
    public function getIsNegativeAttribute(): bool
    {
        return in_array($this->transaction_type, ['withdrawal', 'adjustment']);
    }
}
