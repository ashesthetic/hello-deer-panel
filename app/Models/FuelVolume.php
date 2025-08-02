<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FuelVolume extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'shift',
        'regular_tc_volume',
        'regular_product_height',
        'premium_tc_volume',
        'premium_product_height',
        'diesel_tc_volume',
        'diesel_product_height',
        'added_regular',
        'added_premium',
        'added_diesel',
        'user_id',
    ];

    protected $casts = [
        'date' => 'date',
        'regular_tc_volume' => 'decimal:2',
        'regular_product_height' => 'decimal:2',
        'premium_tc_volume' => 'decimal:2',
        'premium_product_height' => 'decimal:2',
        'diesel_tc_volume' => 'decimal:2',
        'diesel_product_height' => 'decimal:2',
        'added_regular' => 'decimal:2',
        'added_premium' => 'decimal:2',
        'added_diesel' => 'decimal:2',
    ];

    /**
     * Get the user who created this fuel volume entry
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Calculate total volume end of day (evening shift + added data)
     */
    public function getVolumeEndOfDayAttribute()
    {
        $eveningShift = self::where('date', $this->date)
            ->where('shift', 'evening')
            ->first();

        if (!$eveningShift) {
            return null;
        }

        return [
            'regular' => ($eveningShift->regular_tc_volume ?? 0) + ($this->added_regular ?? 0),
            'premium' => ($eveningShift->premium_tc_volume ?? 0) + ($this->added_premium ?? 0),
            'diesel' => ($eveningShift->diesel_tc_volume ?? 0) + ($this->added_diesel ?? 0),
        ];
    }

    /**
     * Get the evening shift data for the same date
     */
    public function getEveningShiftAttribute()
    {
        return self::where('date', $this->date)
            ->where('shift', 'evening')
            ->first();
    }

    /**
     * Get the morning shift data for the same date
     */
    public function getMorningShiftAttribute()
    {
        return self::where('date', $this->date)
            ->where('shift', 'morning')
            ->first();
    }

    /**
     * Scope to get entries by date range
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('date', [$startDate, $endDate]);
    }

    /**
     * Scope to get entries by shift
     */
    public function scopeByShift($query, $shift)
    {
        return $query->where('shift', $shift);
    }

    /**
     * Scope to get entries by user (for permissions)
     */
    public function scopeByUser($query, $user)
    {
        if ($user->isEditor()) {
            return $query->where('user_id', $user->id);
        }
        return $query;
    }
}
