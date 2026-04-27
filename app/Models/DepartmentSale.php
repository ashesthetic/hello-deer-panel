<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DepartmentSale extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'department_number',
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

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_number', 'department_number');
    }
}
