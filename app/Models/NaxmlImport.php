<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NaxmlImport extends Model
{
    protected $fillable = [
        'filename',
        'filepath',
        'business_date',
        'shift_id',
        'store_location_id',
        'status',
        'transaction_count',
        'item_count',
        'financial_event_count',
        'error_message',
        'imported_at',
    ];

    protected $casts = [
        'business_date' => 'date',
        'imported_at' => 'datetime',
    ];

    public function transactions()
    {
        return $this->hasMany(PosTransaction::class);
    }

    public function financialEvents()
    {
        return $this->hasMany(PosFinancialEvent::class);
    }
}
