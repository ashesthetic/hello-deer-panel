<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\WorkSchedule;
use App\Models\WorkScheduleDay;
use App\Models\Employee;
use App\Models\User;
use Carbon\Carbon;

class WorkScheduleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the first user for demo purposes
        $user = User::first();
        
        if (!$user) {
            $this->command->error('No users found. Please run UserSeeder first.');
            return;
        }

        // Get active employees
        $employees = Employee::where('status', 'active')->get();
        
        if ($employees->isEmpty()) {
            $this->command->error('No active employees found. Please run EmployeeSeeder first.');
            return;
        }

        // Create sample schedules for current week and next week
        $currentWeekStart = Carbon::now()->startOfWeek();
        
        foreach ($employees as $index => $employee) {
            // Create current week schedule
            $this->createSampleSchedule($employee, $user, $currentWeekStart, $index);
            
            // Create next week schedule
            $this->createSampleSchedule($employee, $user, $currentWeekStart->copy()->addWeek(), $index);
        }

        $this->command->info('Work schedules created successfully for ' . $employees->count() . ' employees.');
    }

    private function createSampleSchedule($employee, $user, $weekStart, $employeeIndex)
    {
        // Different schedule patterns for different employees
        $schedulePatterns = [
            // Employee 1: Regular 8-hour shifts Monday-Friday
            [
                ['day' => 'monday', 'start' => '09:00', 'end' => '17:00'],
                ['day' => 'tuesday', 'start' => '09:00', 'end' => '17:00'],
                ['day' => 'wednesday', 'start' => '09:00', 'end' => '17:00'],
                ['day' => 'thursday', 'start' => '09:00', 'end' => '17:00'],
                ['day' => 'friday', 'start' => '09:00', 'end' => '17:00'],
                ['day' => 'saturday', 'start' => null, 'end' => null],
                ['day' => 'sunday', 'start' => null, 'end' => null],
            ],
            // Employee 2: Flexible hours with some 6-hour days
            [
                ['day' => 'monday', 'start' => '08:00', 'end' => '14:00'],
                ['day' => 'tuesday', 'start' => '10:00', 'end' => '19:00'],
                ['day' => 'wednesday', 'start' => '09:00', 'end' => '15:00'],
                ['day' => 'thursday', 'start' => '11:00', 'end' => '20:00'],
                ['day' => 'friday', 'start' => '08:00', 'end' => '16:00'],
                ['day' => 'saturday', 'start' => '10:00', 'end' => '16:00'],
                ['day' => 'sunday', 'start' => null, 'end' => null],
            ],
            // Employee 3: Part-time with varied hours
            [
                ['day' => 'monday', 'start' => '14:00', 'end' => '20:00'],
                ['day' => 'tuesday', 'start' => null, 'end' => null],
                ['day' => 'wednesday', 'start' => '16:00', 'end' => '22:00'],
                ['day' => 'thursday', 'start' => null, 'end' => null],
                ['day' => 'friday', 'start' => '12:00', 'end' => '18:00'],
                ['day' => 'saturday', 'start' => '10:00', 'end' => '16:00'],
                ['day' => 'sunday', 'start' => null, 'end' => null],
            ],
            // Employee 4: Weekend worker
            [
                ['day' => 'monday', 'start' => null, 'end' => null],
                ['day' => 'tuesday', 'start' => null, 'end' => null],
                ['day' => 'wednesday', 'start' => null, 'end' => null],
                ['day' => 'thursday', 'start' => null, 'end' => null],
                ['day' => 'friday', 'start' => null, 'end' => null],
                ['day' => 'saturday', 'start' => '08:00', 'end' => '20:00'],
                ['day' => 'sunday', 'start' => '08:00', 'end' => '20:00'],
            ],
        ];

        $pattern = $schedulePatterns[$employeeIndex % count($schedulePatterns)];
        $weekEnd = $weekStart->copy()->endOfWeek();

        // Create work schedule
        $workSchedule = WorkSchedule::create([
            'employee_id' => $employee->id,
            'week_start_date' => $weekStart->format('Y-m-d'),
            'week_end_date' => $weekEnd->format('Y-m-d'),
            'title' => 'Weekly Schedule',
            'notes' => 'Sample schedule for ' . $employee->preferred_name ?? $employee->full_legal_name,
            'status' => 'active',
            'user_id' => $user->id
        ]);

        // Create schedule days
        foreach ($pattern as $dayData) {
            $dayDate = $weekStart->copy()->addDays($this->getDayOffset($dayData['day']));
            
            $startTime = $dayData['start'];
            $endTime = $dayData['end'];
            $hoursWorked = 0;
            $isWorkingDay = false;

            if ($startTime && $endTime) {
                $start = Carbon::parse($startTime);
                $end = Carbon::parse($endTime);
                $hoursWorked = round($start->diffInMinutes($end) / 60, 2);
                $isWorkingDay = true;
            }

            WorkScheduleDay::create([
                'work_schedule_id' => $workSchedule->id,
                'day_of_week' => $dayData['day'],
                'date' => $dayDate->format('Y-m-d'),
                'start_time' => $startTime,
                'end_time' => $endTime,
                'hours_worked' => $hoursWorked,
                'is_working_day' => $isWorkingDay,
                'notes' => $isWorkingDay ? 'Regular shift' : 'Day off'
            ]);
        }
    }

    private function getDayOffset(string $dayOfWeek): int
    {
        $dayMap = [
            'monday' => 0,
            'tuesday' => 1,
            'wednesday' => 2,
            'thursday' => 3,
            'friday' => 4,
            'saturday' => 5,
            'sunday' => 6
        ];

        return $dayMap[$dayOfWeek] ?? 0;
    }
}
