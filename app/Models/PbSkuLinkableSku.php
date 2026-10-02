<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PbSkuLinkableSku extends Model
{
    protected $fillable = [
        'source_id',
        'item_number',
        'linkable_item_number',
        'revision',
    ];

    public function sku()
    {
        return $this->belongsTo(PbSku::class, 'item_number', 'item_number');
    }
}
