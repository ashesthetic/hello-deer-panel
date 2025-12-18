<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use App\Utils\TimezoneUtil;

class WorkSchedule extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'employee_id',
        'week_start_date',
        'week_end_date',
        'title',
        'notes',
        'status',
        'user_id'
    ];

    protected $casts = [
        'week_start_date' => 'date',
        'week_end_date' => 'date',
    ];

    /**
     * Get the employee that owns the work schedule
     */
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * Get the user who created the work schedule
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the schedule days for this schedule
     */
    public function scheduleDays()
    {
        return $this->hasMany(WorkScheduleDay::class);
    }

    /**
     * Scope to get active schedules
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
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
        $today = TimezoneUtil::today();
        return $query->where('week_start_date', '>=', $today);
    }

    /**
     * Get total hours for the week
     */
    public function getTotalHoursForWeek(): float
    {
        return $this->scheduleDays()->sum('hours_worked');
    }

    /**
     * Get working days count
     */
    public function getWorkingDaysCount(): int
    {
        return $this->scheduleDays()->where('is_working_day', true)->count();
    }

    /**
     * Get average hours per working day
     */
    public function getAverageHoursPerDay(): float
    {
        $workingDays = $this->getWorkingDaysCount();
        if ($workingDays === 0) {
            return 0;
        }
        return round($this->getTotalHoursForWeek() / $workingDays, 2);
    }

    /**
     * Check if schedule is for current week
     */
    public function isCurrentWeek(): bool
    {
        $today = TimezoneUtil::today();
        return $this->week_start_date <= $today && $this->week_end_date >= $today;
    }

    /**
     * Check if schedule is for future week
     */
    public function isFutureWeek(): bool
    {
        $today = TimezoneUtil::today();
        return $this->week_start_date > $today;
    }

    /**
     * Get week number
     */
    public function getWeekNumber(): int
    {
        return $this->week_start_date->weekOfYear;
    }

    /**
     * Get formatted week range
     */
    public function getWeekRangeString(): string
    {
        return $this->week_start_date->format('M j') . ' - ' . $this->week_end_date->format('M j, Y');
    }

    /**
     * Get schedule days ordered by day of week
     */
    public function getOrderedScheduleDays()
    {
        $dayOrder = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        
        return $this->scheduleDays()
            ->orderByRaw("FIELD(day_of_week, '" . implode("','", $dayOrder) . "')")
            ->get();
    }

    /**
     * Create schedule days for the week
     */
    public function createScheduleDays(): void
    {
        $currentDate = $this->week_start_date->copy();
        $dayNames = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        
        for ($i = 0; $i < 7; $i++) {
            WorkScheduleDay::create([
                'work_schedule_id' => $this->id,
                'day_of_week' => $dayNames[$i],
                'date' => $currentDate->format('Y-m-d'),
                'start_time' => null,
                'end_time' => null,
                'hours_worked' => 0,
                'is_working_day' => false,
                'notes' => null
            ]);
            
            $currentDate->addDay();
        }
    }

    /**
     * Update schedule day
     */
    public function updateScheduleDay(string $dayOfWeek, array $data): void
    {
        $scheduleDay = $this->scheduleDays()->where('day_of_week', $dayOfWeek)->first();
        
        if ($scheduleDay) {
            // Calculate hours if start and end times are provided
            if (isset($data['start_time']) && isset($data['end_time']) && $data['start_time'] && $data['end_time']) {
                $startTime = Carbon::parse($data['start_time']);
                $endTime = Carbon::parse($data['end_time']);
                $data['hours_worked'] = $startTime->diffInMinutes($endTime) / 60;
                $data['is_working_day'] = true;
            } else {
                $data['hours_worked'] = 0;
                $data['is_working_day'] = false;
            }
            
            $scheduleDay->update($data);
        }
    }
}
