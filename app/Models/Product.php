<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'item_number',
        'name',
        'price',
        'department_number',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'department_number' => 'integer',
    ];

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_number', 'department_number');
    }

    public function itemSales()
    {
        return $this->hasMany(ItemSale::class, 'item_number', 'item_number');
    }
}
