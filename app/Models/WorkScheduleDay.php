<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class WorkScheduleDay extends Model
{
    use HasFactory;

    protected $fillable = [
        'work_schedule_id',
        'day_of_week',
        'date',
        'start_time',
        'end_time',
        'hours_worked',
        'is_working_day',
        'notes'
    ];

    protected $casts = [
        'date' => 'date',
        'start_time' => 'string',
        'end_time' => 'string',
        'hours_worked' => 'decimal:2',
        'is_working_day' => 'boolean',
    ];

    /**
     * Get the work schedule that owns this day
     */
    public function workSchedule()
    {
        return $this->belongsTo(WorkSchedule::class);
    }

    /**
     * Get formatted start time
     */
    public function getFormattedStartTime(): string
    {
        return $this->start_time ? Carbon::parse($this->start_time)->format('g:i A') : 'Off';
    }

    /**
     * Get formatted end time
     */
    public function getFormattedEndTime(): string
    {
        return $this->end_time ? Carbon::parse($this->end_time)->format('g:i A') : 'Off';
    }

    /**
     * Get formatted hours worked
     */
    public function getFormattedHours(): string
    {
        if (!$this->is_working_day) {
            return 'Off';
        }
        return number_format($this->hours_worked, 1) . ' hrs';
    }

    /**
     * Get day name
     */
    public function getDayName(): string
    {
        return ucfirst($this->day_of_week);
    }

    /**
     * Get short day name
     */
    public function getShortDayName(): string
    {
        $dayNames = [
            'monday' => 'Mon',
            'tuesday' => 'Tue',
            'wednesday' => 'Wed',
            'thursday' => 'Thu',
            'friday' => 'Fri',
            'saturday' => 'Sat',
            'sunday' => 'Sun'
        ];
        
        return $dayNames[$this->day_of_week] ?? ucfirst($this->day_of_week);
    }

    /**
     * Check if this is a working day
     */
    public function isWorkingDay(): bool
    {
        return $this->is_working_day && $this->start_time && $this->end_time;
    }

    /**
     * Get time range string
     */
    public function getTimeRangeString(): string
    {
        if (!$this->isWorkingDay()) {
            return 'Day Off';
        }
        
        return $this->getFormattedStartTime() . ' - ' . $this->getFormattedEndTime();
    }

    /**
     * Calculate hours worked
     */
    public function calculateHours(): float
    {
        if (!$this->start_time || !$this->end_time) {
            return 0;
        }

        $startTime = Carbon::parse($this->start_time);
        $endTime = Carbon::parse($this->end_time);
        
        return round($startTime->diffInMinutes($endTime) / 60, 2);
    }

    /**
     * Update hours worked based on start and end times
     */
    public function updateHours(): void
    {
        $this->hours_worked = $this->calculateHours();
        $this->is_working_day = $this->hours_worked > 0;
        $this->save();
    }
}
