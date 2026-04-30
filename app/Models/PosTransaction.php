<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosTransaction extends Model
{
    protected $fillable = [
        'naxml_import_id',
        'store_location_id',
        'shift_id',
        'register_id',
        'cashier_id',
        'transaction_id',
        'sequence_number',
        'business_date',
        'started_at',
        'ended_at',
        'receipt_at',
        'is_training',
        'is_outside_sale',
        'is_offline',
        'is_suspended',
        'total_gross_amount',
        'total_net_amount',
        'total_tax_exempt_amount',
        'total_tax_amount',
        'total_grand_amount',
    ];

    protected $casts = [
        'business_date' => 'date:Y-m-d',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'receipt_at' => 'datetime',
        'is_training' => 'boolean',
        'is_outside_sale' => 'boolean',
        'is_offline' => 'boolean',
        'is_suspended' => 'boolean',
        'total_gross_amount' => 'decimal:2',
        'total_net_amount' => 'decimal:2',
        'total_tax_exempt_amount' => 'decimal:2',
        'total_tax_amount' => 'decimal:2',
        'total_grand_amount' => 'decimal:2',
    ];

    public function import()
    {
        return $this->belongsTo(NaxmlImport::class, 'naxml_import_id');
    }

    public function items()
    {
        return $this->hasMany(PosTransactionItem::class);
    }

    public function tenders()
    {
        return $this->hasMany(PosTransactionTender::class);
    }
}
