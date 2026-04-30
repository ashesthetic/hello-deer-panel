<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PbSkuUpc extends Model
{
    protected $fillable = [
        'item_number',
        'upc',
    ];

    public function sku()
    {
        return $this->belongsTo(PbSku::class, 'item_number', 'item_number');
    }
}
