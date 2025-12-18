<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class ScheduleController extends Controller
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

        $query = Schedule::with(['employee', 'user']);

        // Filter by employee if provided
        if ($request->has('employee_id')) {
            $query->where('employee_id', $request->employee_id);
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

        $schedules = $query->orderBy('week_start_date', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $schedules
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
            'week_end_date' => 'required|date|after_or_equal:week_start_date',
            'shift_info' => 'required|array',
            'shift_info.*.date' => 'required|date',
            'shift_info.*.start_time' => 'required|date_format:H:i',
            'shift_info.*.end_time' => 'required|date_format:H:i',
            'shift_info.*.total_hour' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
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

        // Check for overlapping schedules
        $overlappingSchedule = Schedule::where('employee_id', $request->employee_id)
            ->where(function ($query) use ($request) {
                $query->whereBetween('week_start_date', [$request->week_start_date, $request->week_end_date])
                    ->orWhereBetween('week_end_date', [$request->week_start_date, $request->week_end_date])
                    ->orWhere(function ($q) use ($request) {
                        $q->where('week_start_date', '<=', $request->week_start_date)
                          ->where('week_end_date', '>=', $request->week_end_date);
                    });
            })
            ->first();

        if ($overlappingSchedule) {
            return response()->json([
                'success' => false,
                'message' => 'Employee already has a schedule for this week period'
            ], 422);
        }

        // Calculate total hours
        $totalHours = 0;
        foreach ($request->shift_info as $shift) {
            $totalHours += (float) $shift['total_hour'];
        }

        // Create schedule
        $schedule = Schedule::create([
            'employee_id' => $request->employee_id,
            'week_start_date' => $request->week_start_date,
            'week_end_date' => $request->week_end_date,
            'weekly_total_hours' => round($totalHours, 2),
            'shift_info' => $request->shift_info,
            'notes' => $request->notes,
            'user_id' => auth()->id()
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Schedule created successfully',
            'data' => $schedule->load(['employee', 'user'])
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // Check permissions
        if (!auth()->user()->canCreate()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $schedule = Schedule::with(['employee', 'user'])->find($id);

        if (!$schedule) {
            return response()->json([
                'success' => false,
                'message' => 'Schedule not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $schedule
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // Check permissions
        if (!auth()->user()->canCreate()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $schedule = Schedule::find($id);

        if (!$schedule) {
            return response()->json([
                'success' => false,
                'message' => 'Schedule not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'employee_id' => 'sometimes|required|exists:employees,id',
            'week_start_date' => 'sometimes|required|date',
            'week_end_date' => 'sometimes|required|date|after_or_equal:week_start_date',
            'shift_info' => 'sometimes|required|array',
            'shift_info.*.date' => 'required|date',
            'shift_info.*.start_time' => 'required|date_format:H:i',
            'shift_info.*.end_time' => 'required|date_format:H:i',
            'shift_info.*.total_hour' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // If employee_id is being changed, check if new employee is active
        if ($request->has('employee_id') && $request->employee_id != $schedule->employee_id) {
            $employee = Employee::find($request->employee_id);
            if ($employee->status !== 'active') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot assign schedule to inactive employee'
                ], 422);
            }
        }

        // Check for overlapping schedules if dates are being changed
        if ($request->has('week_start_date') || $request->has('week_end_date') || $request->has('employee_id')) {
            $employeeId = $request->employee_id ?? $schedule->employee_id;
            $weekStart = $request->week_start_date ?? $schedule->week_start_date;
            $weekEnd = $request->week_end_date ?? $schedule->week_end_date;

            $overlappingSchedule = Schedule::where('employee_id', $employeeId)
                ->where('id', '!=', $id)
                ->where(function ($query) use ($weekStart, $weekEnd) {
                    $query->whereBetween('week_start_date', [$weekStart, $weekEnd])
                        ->orWhereBetween('week_end_date', [$weekStart, $weekEnd])
                        ->orWhere(function ($q) use ($weekStart, $weekEnd) {
                            $q->where('week_start_date', '<=', $weekStart)
                              ->where('week_end_date', '>=', $weekEnd);
                        });
                })
                ->first();

            if ($overlappingSchedule) {
                return response()->json([
                    'success' => false,
                    'message' => 'Employee already has a schedule for this week period'
                ], 422);
            }
        }

        // Update schedule
        $updateData = $request->only(['employee_id', 'week_start_date', 'week_end_date', 'shift_info', 'notes']);

        // Recalculate total hours if shift_info is updated
        if ($request->has('shift_info')) {
            $totalHours = 0;
            foreach ($request->shift_info as $shift) {
                $totalHours += (float) $shift['total_hour'];
            }
            $updateData['weekly_total_hours'] = round($totalHours, 2);
        }

        $schedule->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Schedule updated successfully',
            'data' => $schedule->load(['employee', 'user'])
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        // Check permissions
        if (!auth()->user()->canCreate()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $schedule = Schedule::find($id);

        if (!$schedule) {
            return response()->json([
                'success' => false,
                'message' => 'Schedule not found'
            ], 404);
        }

        $schedule->delete();

        return response()->json([
            'success' => true,
            'message' => 'Schedule deleted successfully'
        ]);
    }

    /**
     * Get schedules for current week
     */
    public function currentWeek(Request $request)
    {
        // Check permissions
        if (!auth()->user()->canCreate()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $today = Carbon::today();
        $weekStart = $today->copy()->startOfWeek();
        $weekEnd = $today->copy()->endOfWeek();

        $schedules = Schedule::with(['employee', 'user'])
            ->where('week_start_date', '<=', $weekEnd)
            ->where('week_end_date', '>=', $weekStart)
            ->orderBy('employee_id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $schedules
        ]);
    }

    /**
     * Get schedule statistics
     */
    public function stats(Request $request)
    {
        // Check permissions
        if (!auth()->user()->canCreate()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $today = Carbon::today();
        $weekStart = $today->copy()->startOfWeek();
        $weekEnd = $today->copy()->endOfWeek();

        $currentWeekSchedules = Schedule::where('week_start_date', '<=', $weekEnd)
            ->where('week_end_date', '>=', $weekStart)
            ->get();

        $totalEmployees = Employee::where('status', 'active')->count();
        $scheduledEmployees = $currentWeekSchedules->pluck('employee_id')->unique()->count();
        $totalHours = $currentWeekSchedules->sum('weekly_total_hours');
        $totalShifts = $currentWeekSchedules->sum(function ($schedule) {
            return $schedule->getShiftCount();
        });

        return response()->json([
            'success' => true,
            'data' => [
                'total_active_employees' => $totalEmployees,
                'scheduled_employees' => $scheduledEmployees,
                'unscheduled_employees' => $totalEmployees - $scheduledEmployees,
                'total_weekly_hours' => round($totalHours, 2),
                'total_shifts' => $totalShifts,
                'average_hours_per_employee' => $scheduledEmployees > 0 ? round($totalHours / $scheduledEmployees, 2) : 0
            ]
        ]);
    }

    /**
     * Email schedule to active employees
     */
    public function emailSchedule(Request $request)
    {
        // Check permissions
        if (!auth()->user()->canCreate()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'week_start_date' => 'required|date',
            'pdf_data' => 'required|string', // Base64 encoded PDF
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $weekStartDate = $request->week_start_date;
        
        // Get all schedules for this week
        $schedules = Schedule::with(['employee'])
            ->where('week_start_date', $weekStartDate)
            ->get();

        if ($schedules->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No schedules found for this week'
            ], 404);
        }

        // Get unique active employees from schedules
        $employeeIds = $schedules->pluck('employee_id')->unique();
        $employees = Employee::whereIn('id', $employeeIds)
            ->where('status', 'active')
            ->whereNotNull('email')
            ->get();

        if ($employees->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No active employees with email addresses found'
            ], 404);
        }

        // Decode PDF data
        $pdfData = base64_decode($request->pdf_data);
        
        // Get week range for email subject
        $firstSchedule = $schedules->first();
        $weekStart = Carbon::parse($firstSchedule->week_start_date);
        $weekEnd = Carbon::parse($firstSchedule->week_end_date);
        $weekRange = $weekStart->format('M d') . ' - ' . $weekEnd->format('M d, Y');

        $sentCount = 0;
        $failedEmails = [];
        $errorMessages = [];

        // Send email to each employee
        foreach ($employees as $employee) {
            try {
                Mail::raw("Hello {$employee->preferred_name},\n\nPlease find attached your work schedule for the week of {$weekRange}.\n\nBest regards,\Hello Deer!", function ($message) use ($employee, $weekRange, $pdfData) {
                    $message->to($employee->email)
                        ->subject("Work Schedule - {$weekRange}")
                        ->from(env('MAIL_FROM_ADDRESS', 'noreply@example.com'), 'Hello Deer!')
                        ->attachData($pdfData, 'schedule.pdf', [
                            'mime' => 'application/pdf',
                        ]);
                });
                $sentCount++;
            } catch (\Exception $e) {
                $failedEmails[] = $employee->email;
                $errorMessages[] = $e->getMessage();
                \Log::error('Failed to send schedule email to ' . $employee->email . ': ' . $e->getMessage());
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Schedule sent to {$sentCount} employee(s)",
            'data' => [
                'sent_count' => $sentCount,
                'failed_count' => count($failedEmails),
                'failed_emails' => $failedEmails,
                'error_messages' => $errorMessages
            ]
        ]);
    }
}

