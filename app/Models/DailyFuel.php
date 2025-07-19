<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyFuel extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'regular_quantity',
        'regular_total_sale',
        'plus_quantity',
        'plus_total_sale',
        'sup_plus_quantity',
        'sup_plus_total_sale',
        'diesel_quantity',
        'diesel_total_sale',
        'notes',
        'user_id',
    ];

    protected $casts = [
        'date' => 'date',
        'regular_quantity' => 'decimal:2',
        'regular_total_sale' => 'decimal:3',
        'plus_quantity' => 'decimal:2',
        'plus_total_sale' => 'decimal:3',
        'sup_plus_quantity' => 'decimal:2',
        'sup_plus_total_sale' => 'decimal:3',
        'diesel_quantity' => 'decimal:2',
        'diesel_total_sale' => 'decimal:3',
    ];

    // Calculate total quantity
    public function getTotalQuantityAttribute()
    {
        return ($this->regular_quantity ?? 0) + ($this->plus_quantity ?? 0) + ($this->sup_plus_quantity ?? 0) + ($this->diesel_quantity ?? 0);
    }

    // Calculate total amount (using total_sale fields)
    public function getTotalAmountAttribute()
    {
        return ($this->regular_total_sale ?? 0) + ($this->plus_total_sale ?? 0) + ($this->sup_plus_total_sale ?? 0) + ($this->diesel_total_sale ?? 0);
    }

    // Calculate average price per liter
    public function getAveragePriceAttribute()
    {
        $totalQuantity = $this->total_quantity;
        if ($totalQuantity > 0) {
            return $this->total_amount / $totalQuantity;
        }
        return 0;
    }

    // Calculate price per liter for Regular
    public function getRegularPricePerLiterAttribute()
    {
        $quantity = $this->regular_quantity ?? 0;
        $totalSale = $this->regular_total_sale ?? 0;
        return $quantity > 0 ? $totalSale / $quantity : 0;
    }

    // Calculate price per liter for Plus
    public function getPlusPricePerLiterAttribute()
    {
        $quantity = $this->plus_quantity ?? 0;
        $totalSale = $this->plus_total_sale ?? 0;
        return $quantity > 0 ? $totalSale / $quantity : 0;
    }

    // Calculate price per liter for Sup Plus
    public function getSupPlusPricePerLiterAttribute()
    {
        $quantity = $this->sup_plus_quantity ?? 0;
        $totalSale = $this->sup_plus_total_sale ?? 0;
        return $quantity > 0 ? $totalSale / $quantity : 0;
    }

    // Calculate price per liter for Diesel
    public function getDieselPricePerLiterAttribute()
    {
        $quantity = $this->diesel_quantity ?? 0;
        $totalSale = $this->diesel_total_sale ?? 0;
        return $quantity > 0 ? $totalSale / $quantity : 0;
    }

    /**
     * Relationship with user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
