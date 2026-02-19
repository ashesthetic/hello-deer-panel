<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Document extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'to',
        'document',
    ];

    /**
     * Get the employee that this document is addressed to.
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class, 'to');
    }
}
