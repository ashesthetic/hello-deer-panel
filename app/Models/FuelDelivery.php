<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FuelDelivery extends Model
{
    use HasFactory;

    protected $table = 'fuel_delivery';

    protected $fillable = [
        'delivery_date',
        'invoice_number',
        'regular',
        'premium',
        'diesel',
        'total',
        'amount',
        'issued',
        'issued_date',
        'resolved',
        'resolved_date',
        'note',
    ];

    protected $casts = [
        'delivery_date'  => 'date:Y-m-d',
        'issued_date'    => 'date:Y-m-d',
        'resolved_date'  => 'date:Y-m-d',
        'regular'        => 'decimal:2',
        'premium'        => 'decimal:2',
        'diesel'         => 'decimal:2',
        'total'          => 'decimal:2',
        'amount'         => 'decimal:2',
        'issued'         => 'boolean',
        'resolved'       => 'boolean',
    ];
}
