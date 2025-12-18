<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

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
        'google_drive_file_id',
        'google_drive_file_name',
        'google_drive_web_view_link',
        'subtotal',
        'gst',
        'total',
        'notes',
        'description',
        'user_id',
        'bank_account_id',
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
     * Boot method to automatically calculate subtotal and handle status changes
     */
    protected static function boot()
    {
        parent::boot();
        
        static::saving(function ($vendorInvoice) {
            $vendorInvoice->calculateSubtotal();
        });

        static::updating(function ($vendorInvoice) {
            // Check if status is being changed to 'Paid' or 'paid' (handle both cases)
            if ($vendorInvoice->isDirty('status') && in_array($vendorInvoice->status, ['Paid', 'paid'])) {
                $vendorInvoice->handlePayment();
            }
        });
    }

    /**
     * Handle payment when invoice status changes to paid
     */
    protected function handlePayment()
    {
        try {
            // Only create transaction if we have the necessary data
            if ($this->total > 0) {
                // Get the user - fallback to first admin if no user assigned
                $user = $this->user ?? User::where('role', 'admin')->first() ?? User::first();
                
                if (!$user) {
                    Log::error("No user found for vendor invoice payment transaction", [
                        'invoice_id' => $this->id
                    ]);
                    return;
                }
                
                // Create transaction from vendor invoice payment
                Transaction::createFromVendorInvoice($this, $user);

                Log::info("Transaction created for vendor invoice payment", [
                    'invoice_id' => $this->id,
                    'invoice_number' => $this->invoice_number,
                    'amount' => $this->total,
                    'user_id' => $user->id
                ]);
            } else {
                Log::warning("Cannot create transaction for vendor invoice payment - invalid amount", [
                    'invoice_id' => $this->id,
                    'total' => $this->total
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Error creating transaction for vendor invoice payment", [
                'invoice_id' => $this->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // Don't throw the exception to avoid breaking the invoice update
        }
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
     * Relationship with bank account
     */
    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    /**
     * Relationship with transactions
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
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
     * Scope for filtering by reference
     */
    public function scopeByReference($query, $reference)
    {
        return $query->where('reference', $reference);
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
