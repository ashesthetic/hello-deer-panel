<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lottery extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'lottery';

    protected $fillable = [
        'date',
        'item',
        'shift',
        'start',
        'end',
        'added',
    ];

    protected $casts = [
        'date' => 'date',
        'start' => 'decimal:2',
        'end' => 'decimal:2',
        'added' => 'decimal:2',
    ];
}
