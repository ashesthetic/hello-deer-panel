<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosTransactionTender extends Model
{
    protected $fillable = [
        'pos_transaction_id',
        'tender_code',
        'tender_sub_code',
        'tender_amount',
        'is_change',
    ];

    protected $casts = [
        'tender_amount' => 'decimal:2',
        'is_change' => 'boolean',
    ];

    public function transaction()
    {
        return $this->belongsTo(PosTransaction::class, 'pos_transaction_id');
    }
}
