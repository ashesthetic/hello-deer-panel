<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosFinancialEvent extends Model
{
    protected $fillable = [
        'naxml_import_id',
        'store_location_id',
        'shift_id',
        'register_id',
        'cashier_id',
        'sequence_number',
        'business_date',
        'started_at',
        'ended_at',
        'account_id',
        'account_name',
        'detail_amount',
        'tender_code',
        'tender_sub_code',
        'tender_amount',
    ];

    protected $casts = [
        'business_date' => 'date',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
        'detail_amount' => 'decimal:2',
        'tender_amount' => 'decimal:2',
    ];

    public function import()
    {
        return $this->belongsTo(NaxmlImport::class, 'naxml_import_id');
    }
}
