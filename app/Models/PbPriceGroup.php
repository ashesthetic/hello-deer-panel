<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PbPriceGroup extends Model
{
    protected $primaryKey = 'price_group_number';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'price_group_number',
        'english_description',
        'french_description',
        'price',
        'revision',
    ];

    public function skus()
    {
        return $this->hasMany(PbSku::class, 'price_group_number', 'price_group_number');
    }

    public function quantityPricing()
    {
        return $this->hasMany(PbPriceGroupQuantityPricing::class, 'price_group_number', 'price_group_number');
    }
}
