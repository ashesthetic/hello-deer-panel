<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Department extends Model
{
    use HasFactory, SoftDeletes;

    protected $primaryKey = 'department_number';
    public $incrementing = false;
    protected $keyType = 'integer';

    protected $fillable = [
        'department_number',
        'name',
    ];

    public function products()
    {
        return $this->hasMany(Product::class, 'department_number', 'department_number');
    }
}
