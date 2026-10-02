<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PbSkuLinkedSku extends Model
{
    protected $fillable = [
        'source_id',
        'item_number',
        'linked_item_number',
        'mandatory',
        'revision',
    ];

    protected $casts = [
        'mandatory' => 'boolean',
    ];

    public function sku()
    {
        return $this->belongsTo(PbSku::class, 'item_number', 'item_number');
    }
}
