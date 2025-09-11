<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'bank_name',
        'account_name',
        'account_number',
        'account_type',
        'routing_number',
        'swift_code',
        'currency',
        'balance',
        'is_active',
        'notes',
        'user_id',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that created this bank account.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the bank account can be updated by the given user.
     */
    public function canBeUpdatedBy(User $user): bool
    {
        // Admins can update any bank account
        if ($user->isAdmin()) {
            return true;
        }

        // Editors can only update their own bank accounts
        if ($user->isEditor()) {
            return $this->user_id === $user->id;
        }

        // Viewers cannot update bank accounts
        return false;
    }

    /**
     * Check if the bank account can be deleted by the given user.
     */
    public function canBeDeletedBy(User $user): bool
    {
        // Only admins can delete bank accounts
        return $user->isAdmin();
    }

    /**
     * Scope to filter by active status.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by bank name.
     */
    public function scopeByBank($query, $bankName)
    {
        return $query->where('bank_name', 'like', "%{$bankName}%");
    }

    /**
     * Scope to filter by account type.
     */
    public function scopeByAccountType($query, $accountType)
    {
        return $query->where('account_type', $accountType);
    }

    /**
     * Get formatted balance.
     */
    public function getFormattedBalanceAttribute(): string
    {
        return '$' . number_format($this->balance, 2);
    }

    /**
     * Get masked account number for security.
     */
    public function getMaskedAccountNumberAttribute(): string
    {
        if (strlen($this->account_number) <= 4) {
            return $this->account_number;
        }

        return str_repeat('*', strlen($this->account_number) - 4) . substr($this->account_number, -4);
    }
}
