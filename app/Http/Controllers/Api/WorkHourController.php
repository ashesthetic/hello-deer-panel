<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\WorkHour;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Utils\TimezoneUtil;

class WorkHourController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $workHours = WorkHour::with(['employee', 'user'])
            ->orderBy('date', 'desc')
            ->orderBy('start_time', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $workHours
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'project' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();
        $data['user_id'] = auth()->id();

        // Calculate total hours
        $startTime = TimezoneUtil::parse($data['date'] . ' ' . $data['start_time']);
        $endTime = TimezoneUtil::parse($data['date'] . ' ' . $data['end_time']);
        $data['total_hours'] = $startTime->diffInMinutes($endTime) / 60;

        $workHour = WorkHour::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Work hours recorded successfully',
            'data' => $workHour->load(['employee', 'user'])
        ], 201);
    }

    /**
     * Bulk store work hours from schedule
     */
    public function bulkStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'work_hours' => 'required|array',
            'work_hours.*.employee_id' => 'required|exists:employees,id',
            'work_hours.*.date' => 'required|date',
            'work_hours.*.start_time' => 'required|date_format:H:i',
            'work_hours.*.end_time' => 'required|date_format:H:i',
            'work_hours.*.project' => 'nullable|string|max:255',
            'work_hours.*.description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $userId = auth()->id();
        $createdCount = 0;
        $errors = [];

        foreach ($request->work_hours as $index => $workHourData) {
            try {
                // Calculate total hours
                $startTime = TimezoneUtil::parse($workHourData['date'] . ' ' . $workHourData['start_time']);
                $endTime = TimezoneUtil::parse($workHourData['date'] . ' ' . $workHourData['end_time']);
                
                // Validate that end time is after start time
                if ($endTime->lte($startTime)) {
                    $errors[] = "Entry #" . ($index + 1) . ": End time must be after start time";
                    continue;
                }

                $totalHours = $startTime->diffInMinutes($endTime) / 60;

                WorkHour::create([
                    'employee_id' => $workHourData['employee_id'],
                    'date' => $workHourData['date'],
                    'start_time' => $workHourData['start_time'],
                    'end_time' => $workHourData['end_time'],
                    'total_hours' => $totalHours,
                    'project' => $workHourData['project'] ?? null,
                    'description' => $workHourData['description'] ?? null,
                    'user_id' => $userId,
                ]);

                $createdCount++;
            } catch (\Exception $e) {
                $errors[] = "Entry #" . ($index + 1) . ": " . $e->getMessage();
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Successfully created {$createdCount} work hour entries",
            'created' => $createdCount,
            'failed' => count($errors),
            'errors' => $errors
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(WorkHour $workHour)
    {
        return response()->json([
            'success' => true,
            'data' => $workHour->load(['employee', 'user'])
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, WorkHour $workHour)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'required|exists:employees,id',
            'date' => 'required|date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'project' => 'nullable|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        // Calculate total hours
        $startTime = TimezoneUtil::parse($data['date'] . ' ' . $data['start_time']);
        $endTime = TimezoneUtil::parse($data['date'] . ' ' . $data['end_time']);
        $data['total_hours'] = $startTime->diffInMinutes($endTime) / 60;

        $workHour->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Work hours updated successfully',
            'data' => $workHour->load(['employee', 'user'])
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(WorkHour $workHour)
    {
        $workHour->delete();

        return response()->json([
            'success' => true,
            'message' => 'Work hours deleted successfully'
        ]);
    }

    /**
     * Get work hours for a specific employee
     */
    public function employeeHours(Employee $employee)
    {
        $workHours = WorkHour::where('employee_id', $employee->id)
            ->with('user')
            ->orderBy('date', 'desc')
            ->orderBy('start_time', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $workHours
        ]);
    }

    /**
     * Get recent work hours
     */
    public function recent()
    {
        $workHours = WorkHour::with(['employee', 'user'])
            ->orderBy('date', 'desc')
            ->orderBy('start_time', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $workHours
        ]);
    }

    /**
     * Get work hours summary
     */
    public function summary(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'employee_id' => 'nullable|exists:employees,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $query = WorkHour::query();

        if ($request->employee_id) {
            $query->where('employee_id', $request->employee_id);
        }

        if ($request->start_date) {
            $query->where('date', '>=', $request->start_date);
        }

        if ($request->end_date) {
            $query->where('date', '<=', $request->end_date);
        }

        $totalHours = $query->sum('total_hours');
        $totalEntries = $query->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total_hours' => round($totalHours, 2),
                'total_entries' => $totalEntries,
                'avg_hours_per_entry' => $totalEntries > 0 ? round($totalHours / $totalEntries, 2) : 0
            ]
        ]);
    }
}
