<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailySale extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'fuel_sale',
        'store_sale',
        'gst',
        'card',
        'cash',
        'coupon',
        'delivery',
        'reported_total',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'fuel_sale' => 'decimal:2',
        'store_sale' => 'decimal:2',
        'gst' => 'decimal:2',
        'card' => 'decimal:2',
        'cash' => 'decimal:2',
        'coupon' => 'decimal:2',
        'delivery' => 'decimal:2',
        'reported_total' => 'decimal:2',
    ];

    // Calculate total product sale
    public function getTotalProductSaleAttribute()
    {
        return $this->fuel_sale + $this->store_sale + $this->gst;
    }

    // Calculate total counter sale
    public function getTotalCounterSaleAttribute()
    {
        return $this->card + $this->cash + $this->coupon + $this->delivery;
    }

    // Calculate grand total
    public function getGrandTotalAttribute()
    {
        return $this->total_product_sale + $this->total_counter_sale;
    }
}
