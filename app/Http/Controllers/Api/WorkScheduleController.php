<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkSchedule;
use App\Models\WorkScheduleDay;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Utils\TimezoneUtil;

class WorkScheduleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Check permissions
        if (!auth()->user()->canCreate()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $query = WorkSchedule::with(['employee', 'user', 'scheduleDays']);

        // Filter by employee if provided
        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->employee_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by week start date
        if ($request->has('week_start_date')) {
            $query->where('week_start_date', $request->week_start_date);
        }

        // Filter by date range
        if ($request->has('start_date')) {
            $query->where('week_start_date', '>=', $request->start_date);
        }

        if ($request->has('end_date')) {
            $query->where('week_end_date', '<=', $request->end_date);
        }

        $workSchedules = $query->orderBy('week_start_date', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $workSchedules
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Check permissions
        if (!auth()->user()->canCreate()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'week_start_date' => 'required|date',
            'title' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'status' => 'nullable|in:active,inactive,draft',
            'schedule_days' => 'required|array|size:7',
            'schedule_days.*.day_of_week' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'schedule_days.*.start_time' => 'nullable|date_format:H:i',
            'schedule_days.*.end_time' => 'nullable|date_format:H:i|after:schedule_days.*.start_time',
            'schedule_days.*.notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check if employee is active
        $employee = Employee::find($request->employee_id);
        if ($employee->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Cannot create schedule for inactive employee'
            ], 422);
        }

        // Calculate week end date (Sunday)
        $weekStartDate = Carbon::parse($request->week_start_date);
        $weekEndDate = $weekStartDate->copy()->endOfWeek();

        // Check for overlapping schedules
        $overlappingSchedule = WorkSchedule::where('employee_id', $request->employee_id)
            ->where('status', 'active')
            ->where(function ($query) use ($weekStartDate, $weekEndDate) {
                $query->where(function ($q) use ($weekStartDate, $weekEndDate) {
                    $q->where('week_start_date', '<=', $weekStartDate)
                      ->where('week_end_date', '>=', $weekStartDate);
                })->orWhere(function ($q) use ($weekStartDate, $weekEndDate) {
                    $q->where('week_start_date', '<=', $weekEndDate)
                      ->where('week_end_date', '>=', $weekEndDate);
                })->orWhere(function ($q) use ($weekStartDate, $weekEndDate) {
                    $q->where('week_start_date', '>=', $weekStartDate)
                      ->where('week_end_date', '<=', $weekEndDate);
                });
            })
            ->first();

        if ($overlappingSchedule) {
            return response()->json([
                'success' => false,
                'message' => 'Employee already has an active schedule for this week'
            ], 422);
        }

        // Create work schedule
        $workSchedule = WorkSchedule::create([
            'employee_id' => $request->employee_id,
            'week_start_date' => $weekStartDate->format('Y-m-d'),
            'week_end_date' => $weekEndDate->format('Y-m-d'),
            'title' => $request->title,
            'notes' => $request->notes,
            'status' => $request->status ?? 'active',
            'user_id' => auth()->id()
        ]);

        // Create schedule days
        foreach ($request->schedule_days as $dayData) {
            $dayDate = $weekStartDate->copy()->addDays($this->getDayOffset($dayData['day_of_week']));
            
            $startTime = $dayData['start_time'] ?? null;
            $endTime = $dayData['end_time'] ?? null;
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
                'day_of_week' => $dayData['day_of_week'],
                'date' => $dayDate->format('Y-m-d'),
                'start_time' => $startTime,
                'end_time' => $endTime,
                'hours_worked' => $hoursWorked,
                'is_working_day' => $isWorkingDay,
                'notes' => $dayData['notes'] ?? null
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Work schedule created successfully',
            'data' => $workSchedule->load(['employee', 'user', 'scheduleDays'])
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(WorkSchedule $workSchedule)
    {
        // Check permissions
        if (!auth()->user()->canCreate()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $workSchedule->load(['employee', 'user', 'scheduleDays'])
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, WorkSchedule $workSchedule)
    {
        // Check permissions
        if (!auth()->user()->canUpdate()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:1000',
            'status' => 'nullable|in:active,inactive,draft',
            'schedule_days' => 'sometimes|array|size:7',
            'schedule_days.*.day_of_week' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'schedule_days.*.start_time' => 'nullable|date_format:H:i',
            'schedule_days.*.end_time' => 'nullable|date_format:H:i|after:schedule_days.*.start_time',
            'schedule_days.*.notes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Update work schedule
        $workSchedule->update([
            'title' => $request->title,
            'notes' => $request->notes,
            'status' => $request->status ?? $workSchedule->status
        ]);

        // Update schedule days if provided
        if ($request->has('schedule_days')) {
            foreach ($request->schedule_days as $dayData) {
                $scheduleDay = $workSchedule->scheduleDays()
                    ->where('day_of_week', $dayData['day_of_week'])
                    ->first();

                if ($scheduleDay) {
                    $startTime = $dayData['start_time'] ?? null;
                    $endTime = $dayData['end_time'] ?? null;
                    $hoursWorked = 0;
                    $isWorkingDay = false;

                    if ($startTime && $endTime) {
                        $start = Carbon::parse($startTime);
                        $end = Carbon::parse($endTime);
                        $hoursWorked = round($start->diffInMinutes($end) / 60, 2);
                        $isWorkingDay = true;
                    }

                    $scheduleDay->update([
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'hours_worked' => $hoursWorked,
                        'is_working_day' => $isWorkingDay,
                        'notes' => $dayData['notes'] ?? null
                    ]);
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Work schedule updated successfully',
            'data' => $workSchedule->load(['employee', 'user', 'scheduleDays'])
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(WorkSchedule $workSchedule)
    {
        // Check permissions
        if (!auth()->user()->canDelete()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $workSchedule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Work schedule deleted successfully'
        ]);
    }

    /**
     * Get work schedules for a specific employee
     */
    public function employeeSchedules(Employee $employee)
    {
        // Check permissions
        if (!auth()->user()->canCreate()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $schedules = $employee->workSchedules()
            ->with(['user', 'scheduleDays'])
            ->orderBy('week_start_date', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $schedules
        ]);
    }

    /**
     * Get current week schedules for all employees
     */
    public function currentWeekSchedules()
    {
        // Check permissions
        if (!auth()->user()->canCreate()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $today = TimezoneUtil::today();
        $weekStart = Carbon::parse($today)->startOfWeek();

        $schedules = WorkSchedule::with(['employee', 'user', 'scheduleDays'])
            ->where('week_start_date', $weekStart->format('Y-m-d'))
            ->active()
            ->orderBy('employee_id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $schedules
        ]);
    }

    /**
     * Get employees without current week schedules
     */
    public function employeesWithoutCurrentWeekSchedule()
    {
        // Check permissions
        if (!auth()->user()->canCreate()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $today = TimezoneUtil::today();
        $weekStart = Carbon::parse($today)->startOfWeek();

        $employees = Employee::where('status', 'active')
            ->whereDoesntHave('workSchedules', function ($query) use ($weekStart) {
                $query->where('week_start_date', $weekStart->format('Y-m-d'))
                      ->where('status', 'active');
            })
            ->get();

        return response()->json([
            'success' => true,
            'data' => $employees
        ]);
    }

    /**
     * Get schedule statistics
     */
    public function stats()
    {
        // Check permissions
        if (!auth()->user()->canCreate()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $today = TimezoneUtil::today();
        $weekStart = Carbon::parse($today)->startOfWeek();

        $totalSchedules = WorkSchedule::count();
        $activeSchedules = WorkSchedule::active()->count();
        $currentWeekSchedules = WorkSchedule::where('week_start_date', $weekStart->format('Y-m-d'))->active()->count();
        $employeesWithCurrentSchedule = Employee::whereHas('workSchedules', function ($query) use ($weekStart) {
            $query->where('week_start_date', $weekStart->format('Y-m-d'))
                  ->where('status', 'active');
        })->count();
        $totalActiveEmployees = Employee::where('status', 'active')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_schedules' => $totalSchedules,
                'active_schedules' => $activeSchedules,
                'current_week_schedules' => $currentWeekSchedules,
                'employees_with_current_schedule' => $employeesWithCurrentSchedule,
                'employees_without_current_schedule' => $totalActiveEmployees - $employeesWithCurrentSchedule,
                'total_active_employees' => $totalActiveEmployees,
                'coverage_percentage' => $totalActiveEmployees > 0 ? round(($employeesWithCurrentSchedule / $totalActiveEmployees) * 100, 2) : 0
            ]
        ]);
    }

    /**
     * Get week options for date picker
     */
    public function getWeekOptions()
    {
        // Check permissions
        if (!auth()->user()->canCreate()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $weeks = [];
        $today = TimezoneUtil::today();
        
        // Get the current week's Monday (Carbon's startOfWeek() defaults to Monday)
        $currentWeek = Carbon::parse($today)->startOfWeek(Carbon::MONDAY);

        // Generate options for current week and next 4 weeks
        for ($i = 0; $i < 5; $i++) {
            $weekStart = $currentWeek->copy()->addWeeks($i);
            $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);
            
            $weeks[] = [
                'value' => $weekStart->format('Y-m-d'),
                'label' => $weekStart->format('M j') . ' - ' . $weekEnd->format('M j, Y'),
                'week_number' => $weekStart->weekOfYear
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $weeks
        ]);
    }

    /**
     * Helper method to get day offset from Monday
     */
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
