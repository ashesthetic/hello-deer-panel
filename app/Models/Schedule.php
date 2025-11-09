<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Schedule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'week_start_date',
        'week_end_date',
        'weekly_total_hours',
        'shift_info',
        'notes',
        'user_id'
    ];

    protected $casts = [
        'weekly_total_hours' => 'decimal:2',
        'shift_info' => 'array',
    ];

    protected $dates = [
        'week_start_date',
        'week_end_date',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    /**
     * Prepare dates for JSON serialization (format as Y-m-d only)
     */
    protected function serializeDate(\DateTimeInterface $date): string
    {
        // For week dates, return just Y-m-d format
        return $date->format('Y-m-d');
    }

    /**
     * Get the employee that owns the schedule
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the user who created the schedule
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Calculate total hours from shift_info
     */
    public function calculateTotalHours(): float
    {
        if (!$this->shift_info || !is_array($this->shift_info)) {
            return 0;
        }

        $totalHours = 0;
        foreach ($this->shift_info as $shift) {
            if (isset($shift['total_hour'])) {
                $totalHours += (float) $shift['total_hour'];
            }
        }

        return round($totalHours, 2);
    }

    /**
     * Update weekly total hours based on shift_info
     */
    public function updateTotalHours(): void
    {
        $this->weekly_total_hours = $this->calculateTotalHours();
        $this->save();
    }

    /**
     * Get shifts for a specific date
     */
    public function getShiftsForDate(string $date): ?array
    {
        if (!$this->shift_info || !is_array($this->shift_info)) {
            return null;
        }

        foreach ($this->shift_info as $shift) {
            if (isset($shift['date']) && $shift['date'] === $date) {
                return $shift;
            }
        }

        return null;
    }

    /**
     * Scope to get schedules for a specific employee
     */
    public function scopeForEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    /**
     * Scope to get schedules for a specific week
     */
    public function scopeForWeek($query, $weekStartDate)
    {
        return $query->where('week_start_date', $weekStartDate);
    }

    /**
     * Scope to get current and future schedules
     */
    public function scopeCurrentAndFuture($query)
    {
        $today = Carbon::today();
        return $query->where('week_start_date', '>=', $today);
    }

    /**
     * Check if schedule is for current week
     */
    public function isCurrentWeek(): bool
    {
        $today = Carbon::today();
        return $this->week_start_date <= $today && $this->week_end_date >= $today;
    }

    /**
     * Get formatted week range
     */
    public function getWeekRangeString(): string
    {
        return $this->week_start_date->format('M j') . ' - ' . $this->week_end_date->format('M j, Y');
    }

    /**
     * Get number of shifts
     */
    public function getShiftCount(): int
    {
        return $this->shift_info ? count($this->shift_info) : 0;
    }

    /**
     * Validate shift_info structure
     */
    public static function validateShiftInfo(array $shiftInfo): bool
    {
        foreach ($shiftInfo as $shift) {
            if (!isset($shift['date']) || !isset($shift['start_time']) || !isset($shift['end_time']) || !isset($shift['total_hour'])) {
                return false;
            }
        }
        return true;
    }
}
