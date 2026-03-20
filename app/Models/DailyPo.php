<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DailyPo extends Model
{
    use SoftDeletes;

    protected $table = 'daily_pos';

    protected $fillable = [
        'date',
        'amount',
        'resolved',
        'notes',
    ];

    protected $casts = [
        'date'     => 'date',
        'amount'   => 'decimal:2',
        'resolved' => 'boolean',
    ];
}
