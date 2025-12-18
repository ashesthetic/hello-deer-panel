<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Utils\TimezoneUtil;

class OwnerEquity extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'date',
        'description',
        'type',
        'amount',
        'note',
        'owner_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date' => 'date',
    ];

    /**
     * The accessors to append to the model's array form.
     */
    protected $appends = [
        'formatted_date',
        'formatted_amount',
        'type_display',
        'is_investment',
        'is_withdrawal',
    ];

    /**
     * Get the owner for this equity transaction
     */
    public function owner(): BelongsTo
    {
        return $this->belongsTo(Owner::class);
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
        return $query->where('type', $type);
    }

    /**
     * Scope to get transactions by date range
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Get the formatted date in Alberta timezone
     */
    public function getFormattedDateAttribute(): string
    {
        return TimezoneUtil::formatDate($this->date);
    }

    /**
     * Get the formatted amount with currency
     */
    public function getFormattedAmountAttribute(): string
    {
        return '$' . number_format($this->amount, 2);
    }

    /**
     * Get the type display name
     */
    public function getTypeDisplayAttribute(): string
    {
        return ucfirst($this->type);
    }

    /**
     * Check if this is an investment transaction
     */
    public function getIsInvestmentAttribute(): bool
    {
        return $this->type === 'investment';
    }

    /**
     * Check if this is a withdrawal transaction
     */
    public function getIsWithdrawalAttribute(): bool
    {
        return $this->type === 'withdrawal';
    }
}
