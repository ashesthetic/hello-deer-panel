<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Smokes extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'date',
        'item',
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
