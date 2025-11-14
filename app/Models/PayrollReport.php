<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollReport extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'file_name',
        'file_path',
        'original_name',
        'file_size',
        'extracted_text',
        'parsed_data',
        'status',
        'user_id',
    ];

    protected $casts = [
        'parsed_data' => 'array',
    ];

    /**
     * Get the user that uploaded the report.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
