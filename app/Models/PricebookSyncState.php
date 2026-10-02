<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PricebookSyncState extends Model
{
    protected $table = 'pricebook_sync_state';

    protected $fillable = [
        'last_revision',
        'head_etag',
        'running_since',
        'last_success_at',
        'last_error',
        'last_error_at',
        'last_snapshot_revision',
    ];

    protected $casts = [
        'last_revision' => 'integer',
        'running_since' => 'datetime',
        'last_success_at' => 'datetime',
        'last_error_at' => 'datetime',
        'last_snapshot_revision' => 'integer',
    ];

    public static function current(): self
    {
        return static::query()->first() ?? static::create([]);
    }
}
