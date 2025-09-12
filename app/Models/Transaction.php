<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'amount',
        'description',
        'notes',
        'bank_account_id',
        'from_bank_account_id',
        'to_bank_account_id',
        'vendor_invoice_id',
        'transaction_date',
        'reference_number',
        'status',
        'user_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
    ];

    /**
     * Relationship with user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relationship with bank account (for income/expense)
     */
    public function bankAccount()
    {
        return $this->belongsTo(BankAccount::class);
    }

    /**
     * Relationship with from bank account (for transfers)
     */
    public function fromBankAccount()
    {
        return $this->belongsTo(BankAccount::class, 'from_bank_account_id');
    }

    /**
     * Relationship with to bank account (for transfers)
     */
    public function toBankAccount()
    {
        return $this->belongsTo(BankAccount::class, 'to_bank_account_id');
    }

    /**
     * Relationship with vendor invoice
     */
    public function vendorInvoice()
    {
        return $this->belongsTo(VendorInvoice::class);
    }

    /**
     * Check if user can view this transaction
     */
    public function canBeViewedBy(User $user): bool
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
     * Check if user can update this transaction
     */
    public function canBeUpdatedBy(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        
        // Editors cannot update auto-generated transactions
        if ($this->vendor_invoice_id && $user->isEditor()) {
            return false;
        }
        
        if ($user->isEditor()) {
            return $this->user_id === $user->id;
        }
        
        return false;
    }

    /**
     * Check if user can delete this transaction
     */
    public function canBeDeletedBy(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }
        
        return false;
    }

    /**
     * Create a transaction from vendor invoice payment
     */
    public static function createFromVendorInvoice(VendorInvoice $invoice, User $user): self
    {
        // Determine transaction type - vendor invoices are usually expenses
        $type = 'expense';
        
        // If the invoice has a specific bank account, use it
        $bankAccount = null;
        if ($invoice->bank_account_id) {
            $bankAccount = BankAccount::find($invoice->bank_account_id);
        }
        
        // If no bank account specified or found, get the primary bank account
        if (!$bankAccount) {
            $bankAccount = BankAccount::where('is_active', true)->first();
        }
        
        if (!$bankAccount) {
            throw new \Exception('No active bank account found for transaction');
        }
        
        // Create description with vendor name if available
        $vendorName = $invoice->vendor ? $invoice->vendor->name : 'Unknown Vendor';
        $description = "Payment for invoice #{$invoice->invoice_number} - {$vendorName}";
        
        $transaction = self::create([
            'type' => $type,
            'amount' => $invoice->total,
            'description' => $description,
            'notes' => "Auto-generated from vendor invoice payment",
            'bank_account_id' => $bankAccount->id,
            'vendor_invoice_id' => $invoice->id,
            'transaction_date' => $invoice->payment_date ?? now()->toDateString(),
            'reference_number' => $invoice->invoice_number,
            'status' => 'completed',
            'user_id' => $user->id,
        ]);
        
        // Update bank account balance (expense decreases balance)
        $bankAccount->decrement('balance', $invoice->total);
        
        return $transaction;
    }

    /**
     * Create a transfer transaction
     */
    public static function createTransfer(
        BankAccount $fromAccount,
        BankAccount $toAccount,
        float $amount,
        string $description,
        ?string $notes,
        User $user
    ): self {
        $transaction = self::create([
            'type' => 'transfer',
            'amount' => $amount,
            'description' => $description,
            'notes' => $notes,
            'from_bank_account_id' => $fromAccount->id,
            'to_bank_account_id' => $toAccount->id,
            'transaction_date' => now()->toDateString(),
            'reference_number' => 'TXF-' . now()->format('YmdHis'),
            'status' => 'completed',
            'user_id' => $user->id,
        ]);
        
        // Update bank account balances
        $fromAccount->decrement('balance', $amount);
        $toAccount->increment('balance', $amount);
        
        return $transaction;
    }

    /**
     * Scope for filtering by type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for filtering by date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate, $endDate]);
    }

    /**
     * Scope for filtering by bank account
     */
    public function scopeForBankAccount($query, int $bankAccountId)
    {
        return $query->where(function($q) use ($bankAccountId) {
            $q->where('bank_account_id', $bankAccountId)
              ->orWhere('from_bank_account_id', $bankAccountId)
              ->orWhere('to_bank_account_id', $bankAccountId);
        });
    }
}
