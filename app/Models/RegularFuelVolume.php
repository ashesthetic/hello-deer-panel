<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegularFuelVolume extends Model
{
    use HasFactory;

    protected $table = 'regular_fuel_volume';

    protected $fillable = [
        'datetime',
        'regular_volume',
        'regular_height',
        'regular_ullage',
        'regular_water',
        'regular_temp',
        'regular_fill',
        'regular_status',
        'premium_volume',
        'premium_height',
        'premium_ullage',
        'premium_water',
        'premium_temp',
        'premium_fill',
        'premium_status',
        'diesel_volume',
        'diesel_height',
        'diesel_ullage',
        'diesel_water',
        'diesel_temp',
        'diesel_fill',
        'diesel_status',
    ];

    protected $casts = [
        'datetime'        => 'datetime',
        'regular_volume'  => 'decimal:2',
        'regular_height'  => 'decimal:2',
        'regular_ullage'  => 'decimal:2',
        'regular_water'   => 'decimal:2',
        'regular_temp'    => 'decimal:2',
        'regular_fill'    => 'decimal:2',
        'premium_volume'  => 'decimal:2',
        'premium_height'  => 'decimal:2',
        'premium_ullage'  => 'decimal:2',
        'premium_water'   => 'decimal:2',
        'premium_temp'    => 'decimal:2',
        'premium_fill'    => 'decimal:2',
        'diesel_volume'   => 'decimal:2',
        'diesel_height'   => 'decimal:2',
        'diesel_ullage'   => 'decimal:2',
        'diesel_water'    => 'decimal:2',
        'diesel_temp'     => 'decimal:2',
        'diesel_fill'     => 'decimal:2',
    ];

    /**
     * Scope to get entries by datetime range
     */
    public function scopeDatetimeRange($query, $startDatetime, $endDatetime)
    {
        return $query->whereBetween('datetime', [$startDatetime, $endDatetime]);
    }

    /**
     * Scope to get entries by date (ignoring time)
     */
    public function scopeByDate($query, $date)
    {
        return $query->whereDate('datetime', $date);
    }

    /**
     * Scope to get entries by month and year
     */
    public function scopeByMonth($query, $year, $month)
    {
        return $query->whereYear('datetime', $year)
                     ->whereMonth('datetime', $month);
    }
}
