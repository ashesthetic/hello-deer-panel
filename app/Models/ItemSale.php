<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ItemSale extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'item_number',
        'department_number',
        'name',
        'qty',
        'price',
        'date',
    ];

    protected $casts = [
        'department_number' => 'integer',
        'qty' => 'decimal:2',
        'price' => 'decimal:2',
        'date' => 'date:Y-m-d',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'item_number', 'item_number');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_number', 'department_number');
    }
}
