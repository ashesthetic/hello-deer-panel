<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DailyFuel extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'fuel_type',
        'quantity',
        'price_per_liter',
        'total_amount',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'quantity' => 'decimal:2',
        'price_per_liter' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];
}
