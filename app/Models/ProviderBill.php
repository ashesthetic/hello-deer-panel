<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProviderBill extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_id',
        'billing_date',
        'service_date_from',
        'service_date_to',
        'due_date',
        'subtotal',
        'gst',
        'total',
        'notes',
        'invoice_file_path',
        'status',
        'date_paid',
        'user_id',
    ];

    protected $casts = [
        'billing_date' => 'date',
        'service_date_from' => 'date',
        'service_date_to' => 'date',
        'due_date' => 'date',
        'subtotal' => 'decimal:2',
        'gst' => 'decimal:2',
        'total' => 'decimal:2',
        'date_paid' => 'date',
    ];

    /**
     * Relationship with provider
     */
    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    /**
     * Relationship with user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if user can update this provider bill
     */
    public function canBeUpdatedBy(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        
        if ($user->isEditor()) {
            return $this->user_id === $user->id;
        }
        
        return false;
    }

    /**
     * Check if user can delete this provider bill
     */
    public function canBeDeletedBy(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        
        return false;
    }

    /**
     * Scope for pending bills
     */
    public function scopePending($query)
    {
        return $query->where('status', 'Pending');
    }

    /**
     * Scope for paid bills
     */
    public function scopePaid($query)
    {
        return $query->where('status', 'Paid');
    }

    /**
     * Scope for bills by provider
     */
    public function scopeByProvider($query, $providerId)
    {
        return $query->where('provider_id', $providerId);
    }

    /**
     * Scope for bills by date range
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('billing_date', [$startDate, $endDate]);
    }

    /**
     * Scope for bills by payment date range
     */
    public function scopeByPaymentDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date_paid', [$startDate, $endDate]);
    }

    /**
     * Scope for overdue bills
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', 'Pending')
                    ->where('due_date', '<', \App\Utils\TimezoneUtil::now()->toDateString());
    }

    /**
     * Calculate subtotal based on total and GST
     */
    public function calculateSubtotal()
    {
        $this->subtotal = $this->total - $this->gst;
        return $this->subtotal;
    }

    /**
     * Boot method to automatically calculate subtotal before saving
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($providerBill) {
            $providerBill->calculateSubtotal();
        });
    }
}
