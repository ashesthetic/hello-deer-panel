<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DailyAtm extends Model
{
    use SoftDeletes;

    protected $table = 'daily_atm';

    protected $fillable = [
        'date',
        'no_of_transactions',
        'withdraw',
        'fee',
        'resolved',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'no_of_transactions' => 'integer',
        'withdraw' => 'decimal:2',
        'fee' => 'decimal:2',
        'resolved' => 'boolean',
    ];
}
