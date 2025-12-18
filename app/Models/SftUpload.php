<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SftUpload extends Model
{
    use HasFactory;

    protected $fillable = [
        'upload_date',
        'file_name',
        'file_path',
        'uploaded_by',
    ];

    protected $casts = [
        'upload_date' => 'date',
    ];

    /**
     * Get the user who uploaded the file
     */
    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get the full file path
     */
    public function getFullPath(): string
    {
        return storage_path('app/' . $this->file_path);
    }
}
