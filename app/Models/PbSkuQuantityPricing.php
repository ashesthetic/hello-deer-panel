<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PbSkuQuantityPricing extends Model
{
    protected $fillable = [
        'source_id',
        'item_number',
        'quantity',
        'price',
        'revision',
    ];

    public function sku()
    {
        return $this->belongsTo(PbSku::class, 'item_number', 'item_number');
    }
}
