<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FileImport extends Model
{
    use HasFactory;

    protected $fillable = [
        'import_date',
        'file_name',
        'original_name',
        'file_path',
        'file_size',
        'mime_type',
        'processed',
        'notes',
    ];

    protected $casts = [
        'import_date' => 'date',
        'processed' => 'integer',
        'file_size' => 'integer',
    ];

    /**
     * Check if the file has been processed
     */
    public function isProcessed(): bool
    {
        return $this->processed === 1;
    }

    /**
     * Mark the file as processed
     */
    public function markAsProcessed(): void
    {
        $this->update(['processed' => 1]);
    }

    /**
     * Mark the file as not processed
     */
    public function markAsNotProcessed(): void
    {
        $this->update(['processed' => 0]);
    }

    /**
     * Get the full file path
     */
    public function getFullPath(): string
    {
        return storage_path('app/' . $this->file_path);
    }

    /**
     * Get the file extension
     */
    public function getExtension(): string
    {
        return pathinfo($this->original_name, PATHINFO_EXTENSION);
    }
}
