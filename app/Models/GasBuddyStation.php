<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GasBuddyStation extends Model
{
    protected $table = 'gasbuddy_stations';

    protected $fillable = [
        'gasbuddy_station_id',
        'name',
        'address_line1',
        'address_line2',
        'distance',
        'regular_gas',
        'midgrade_gas',
        'premium_gas',
        'diesel',
        'regular_gas_posted_at',
        'midgrade_gas_posted_at',
        'premium_gas_posted_at',
        'diesel_posted_at',
        'last_fetched_at',
    ];

    protected $casts = [
        'regular_gas'             => 'decimal:3',
        'midgrade_gas'            => 'decimal:3',
        'premium_gas'             => 'decimal:3',
        'diesel'                  => 'decimal:3',
        'regular_gas_posted_at'   => 'datetime',
        'midgrade_gas_posted_at'  => 'datetime',
        'premium_gas_posted_at'   => 'datetime',
        'diesel_posted_at'        => 'datetime',
        'last_fetched_at'         => 'datetime',
    ];

    /**
     * Sort by numeric distance (distance is stored as e.g. "0.01mi").
     */
    public function scopeOrderByDistance($query)
    {
        return $query->orderByRaw('CAST(distance AS DECIMAL(8,4)) ASC');
    }
}
