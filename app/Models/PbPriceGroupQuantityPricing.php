<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PbPriceGroupQuantityPricing extends Model
{
    protected $fillable = [
        'source_id',
        'price_group_number',
        'quantity',
        'price',
        'revision',
    ];

    public function priceGroup()
    {
        return $this->belongsTo(PbPriceGroup::class, 'price_group_number', 'price_group_number');
    }
}
