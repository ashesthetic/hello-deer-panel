<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VendorInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_id',
        'invoice_number',
        'invoice_date',
        'status',
        'type',
        'reference',
        'payment_date',
        'payment_method',
        'invoice_file_path',
        'subtotal',
        'gst',
        'total',
        'notes',
        'description',
        'user_id',
    ];

    protected $casts = [
        'invoice_date' => 'date',
        'payment_date' => 'date',
        'subtotal' => 'decimal:2',
        'gst' => 'decimal:2',
        'total' => 'decimal:2',
    ];

    /**
     * Calculate subtotal from total and GST
     */
    public function calculateSubtotal()
    {
        $this->subtotal = $this->total - $this->gst;
        return $this->subtotal;
    }

    /**
     * Boot method to automatically calculate subtotal
     */
    protected static function boot()
    {
        parent::boot();
        static::saving(function ($vendorInvoice) {
            $vendorInvoice->calculateSubtotal();
        });
    }

    /**
     * Relationship with vendor
     */
    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    /**
     * Relationship with user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if user can update this vendor invoice
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
     * Check if user can delete this vendor invoice
     */
    public function canBeDeletedBy(User $user): bool
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
     * Scope for filtering by status
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for filtering by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for filtering by vendor
     */
    public function scopeByVendor($query, $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    /**
     * Scope for filtering by date range
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('invoice_date', [$startDate, $endDate]);
    }

    /**
     * Scope for filtering by payment date range
     */
    public function scopeByPaymentDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('payment_date', [$startDate, $endDate]);
    }
}
