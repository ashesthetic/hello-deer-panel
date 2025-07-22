<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Carbon\Carbon;
use App\Utils\TimezoneUtil;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $employees = Employee::with('user')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $employees
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_legal_name' => 'nullable|string|max:255',
            'preferred_name' => 'nullable|string|max:255',
            'date_of_birth' => 'nullable|date',
            'address' => 'nullable|string',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|in:Canada,United States',
            'phone_number' => 'nullable|string|max:20',
            'alternate_number' => 'nullable|string|max:20',
            'email' => 'nullable|email|unique:employees,email',
            'emergency_name' => 'nullable|string|max:255',
            'emergency_relationship' => 'nullable|string|max:255',
            'emergency_address_line1' => 'nullable|string|max:255',
            'emergency_address_line2' => 'nullable|string|max:255',
            'emergency_city' => 'nullable|string|max:255',
            'emergency_state' => 'nullable|string|max:255',
            'emergency_postal_code' => 'nullable|string|max:20',
            'emergency_country' => 'nullable|string|in:Canada,United States',
            'emergency_phone' => 'nullable|string|max:20',
            'emergency_alternate_number' => 'nullable|string|max:20',
            'status_in_canada' => 'nullable|string|in:Canadian Citizen,Permanent Resident,Work Permit + Student,Work Permit,Other',
            'other_status' => 'nullable|string|max:255',
            'sin_number' => 'nullable|string|max:20',
            'position' => 'nullable|string|in:Director,Manager,Store Associate',
            'department' => 'nullable|string|max:255',
            'hire_date' => 'nullable|date',
            'hourly_rate' => 'nullable|numeric|min:0',
            'status' => 'nullable|string|in:active,inactive',
            'facebook' => 'nullable|url|max:255',
            'linkedin' => 'nullable|url|max:255',
            'twitter' => 'nullable|url|max:255',
            'government_id_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'work_permit_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'resume_file' => 'nullable|file|mimes:pdf,doc,docx|max:2048',
            'photo_file' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'void_cheque_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
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

        // Handle file uploads
        $fileFields = ['government_id_file', 'work_permit_file', 'resume_file', 'photo_file', 'void_cheque_file'];
        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $filename = time() . '_' . $field . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('employees/' . $field, $filename, 'public');
                $data[$field] = $path;
            }
        }

        $employee = Employee::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Employee created successfully',
            'data' => $employee->load('user')
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Employee $employee)
    {
        return response()->json([
            'success' => true,
            'data' => $employee->load('user')
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Employee $employee)
    {
        $validator = Validator::make($request->all(), [
            'full_legal_name' => 'nullable|string|max:255',
            'preferred_name' => 'nullable|string|max:255',
            'date_of_birth' => 'nullable|date',
            'address' => 'nullable|string',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|in:Canada,United States',
            'phone_number' => 'nullable|string|max:20',
            'alternate_number' => 'nullable|string|max:20',
            'email' => ['nullable', 'email', Rule::unique('employees')->ignore($employee->id)],
            'emergency_name' => 'nullable|string|max:255',
            'emergency_relationship' => 'nullable|string|max:255',
            'emergency_address_line1' => 'nullable|string|max:255',
            'emergency_address_line2' => 'nullable|string|max:255',
            'emergency_city' => 'nullable|string|max:255',
            'emergency_state' => 'nullable|string|max:255',
            'emergency_postal_code' => 'nullable|string|max:20',
            'emergency_country' => 'nullable|string|in:Canada,United States',
            'emergency_phone' => 'nullable|string|max:20',
            'emergency_alternate_number' => 'nullable|string|max:20',
            'status_in_canada' => 'nullable|string|in:Canadian Citizen,Permanent Resident,Work Permit + Student,Work Permit,Other',
            'other_status' => 'nullable|string|max:255',
            'sin_number' => 'nullable|string|max:20',
            'position' => 'nullable|string|in:Director,Manager,Store Associate',
            'department' => 'nullable|string|max:255',
            'hire_date' => 'nullable|date',
            'hourly_rate' => 'nullable|numeric|min:0',
            'status' => 'nullable|string|in:active,inactive',
            'facebook' => 'nullable|url|max:255',
            'linkedin' => 'nullable|url|max:255',
            'twitter' => 'nullable|url|max:255',
            'government_id_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'work_permit_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'resume_file' => 'nullable|file|mimes:pdf,doc,docx|max:2048',
            'photo_file' => 'nullable|file|mimes:jpg,jpeg,png|max:2048',
            'void_cheque_file' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $validator->validated();

        // Handle file uploads
        $fileFields = ['government_id_file', 'work_permit_file', 'resume_file', 'photo_file', 'void_cheque_file'];
        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                // Delete old file if exists
                if ($employee->$field) {
                    Storage::disk('public')->delete($employee->$field);
                }
                
                $file = $request->file($field);
                $filename = time() . '_' . $field . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('employees/' . $field, $filename, 'public');
                $data[$field] = $path;
            }
        }

        $employee->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Employee updated successfully',
            'data' => $employee->load('user')
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Employee $employee)
    {
        // Delete associated files
        $fileFields = ['government_id_file', 'work_permit_file', 'resume_file', 'photo_file', 'void_cheque_file'];
        foreach ($fileFields as $field) {
            if ($employee->$field) {
                Storage::disk('public')->delete($employee->$field);
            }
        }

        $employee->delete();

        return response()->json([
            'success' => true,
            'message' => 'Employee deleted successfully'
        ]);
    }

    /**
     * Get employee statistics
     */
    public function stats()
    {
        $totalEmployees = Employee::count();
        $activeEmployees = Employee::where('status', 'active')->count();
        $inactiveEmployees = Employee::where('status', 'inactive')->count();

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $totalEmployees,
                'active' => $activeEmployees,
                'inactive' => $inactiveEmployees,
                'avg_tenure' => '1.2 yrs' // This would be calculated in a real app
            ]
        ]);
    }

    /**
     * Get employee earnings for current and next pay periods
     */
    public function earnings()
    {
        // Calculate current pay period dates based on bi-weekly Thursday pay schedule
        $firstPayDay = TimezoneUtil::parse('2025-07-24'); // First pay day (Thursday)
        $today = TimezoneUtil::now();
        
        // Find the current pay period
        // Pay period is exactly 2 weeks (14 days) before the pay day
        // Example: Payday July 24, 2025 → Pay period July 4-17, 2025
        
        // Calculate how many pay periods have passed since the first pay day
        $weeksSinceFirstPay = $today->diffInWeeks($firstPayDay, false);
        $payPeriodsPassed = floor($weeksSinceFirstPay / 2);
        
        // Current pay day is the first pay day minus the passed pay periods
        $currentPayDay = $firstPayDay->copy()->subWeeks($payPeriodsPassed * 2);
        
        // Calculate the work period (14 days ending 7 days before pay day)
        // For July 24 pay day: period is July 4-17 (work period ends 7 days before pay day)
        $currentPeriodStart = $currentPayDay->copy()->subDays(20);
        $currentPeriodEnd = $currentPayDay->copy()->subDays(7);
        
        // Get next pay day and period
        $nextPayDay = $currentPayDay->copy()->addWeeks(2);
        // For August 7 pay day: period is July 18-31 (work period ends 7 days before next pay day)
        $nextPeriodStart = $nextPayDay->copy()->subDays(20);
        $nextPeriodEnd = $nextPayDay->copy()->subDays(7);
        
        // Get all active employees
        $employees = Employee::where('status', 'active')->get();
        
        $currentEarningsData = [];
        $nextEarningsData = [];
        
        foreach ($employees as $employee) {
            // Get work hours for current period
            $currentWorkHours = $employee->workHours()
                ->whereBetween('date', [$currentPeriodStart->format('Y-m-d'), $currentPeriodEnd->format('Y-m-d')])
                ->get();
            
            $currentTotalHours = $currentWorkHours->sum('total_hours');
            $currentTotalEarnings = $currentTotalHours * $employee->hourly_rate;
            
            $currentEarningsData[] = [
                'id' => $employee->id,
                'full_legal_name' => $employee->full_legal_name,
                'preferred_name' => $employee->preferred_name,
                'position' => $employee->position,
                'department' => $employee->department,
                'hourly_rate' => $employee->hourly_rate,
                'total_hours' => round($currentTotalHours, 2),
                'total_earnings' => round($currentTotalEarnings, 2),
                'work_days' => $currentWorkHours->count(),
                'period_start' => $currentPeriodStart->format('Y-m-d'),
                'period_end' => $currentPeriodEnd->format('Y-m-d'),
                'pay_day' => $currentPayDay->format('Y-m-d')
            ];
            
            // Get work hours for next period
            $nextWorkHours = $employee->workHours()
                ->whereBetween('date', [$nextPeriodStart->format('Y-m-d'), $nextPeriodEnd->format('Y-m-d')])
                ->get();
            
            $nextTotalHours = $nextWorkHours->sum('total_hours');
            $nextTotalEarnings = $nextTotalHours * $employee->hourly_rate;
            
            $nextEarningsData[] = [
                'id' => $employee->id,
                'full_legal_name' => $employee->full_legal_name,
                'preferred_name' => $employee->preferred_name,
                'position' => $employee->position,
                'department' => $employee->department,
                'hourly_rate' => $employee->hourly_rate,
                'total_hours' => round($nextTotalHours, 2),
                'total_earnings' => round($nextTotalEarnings, 2),
                'work_days' => $nextWorkHours->count(),
                'period_start' => $nextPeriodStart->format('Y-m-d'),
                'period_end' => $nextPeriodEnd->format('Y-m-d'),
                'pay_day' => $nextPayDay->format('Y-m-d')
            ];
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'current_period' => [
                    'employees' => $currentEarningsData,
                    'period_info' => [
                        'period_start' => $currentPeriodStart->format('Y-m-d'),
                        'period_end' => $currentPeriodEnd->format('Y-m-d'),
                        'pay_day' => $currentPayDay->format('Y-m-d'),
                        'days_until_pay' => max(0, ceil($today->diffInDays($currentPayDay, true)))
                    ]
                ],
                'next_period' => [
                    'employees' => $nextEarningsData,
                    'period_info' => [
                        'period_start' => $nextPeriodStart->format('Y-m-d'),
                        'period_end' => $nextPeriodEnd->format('Y-m-d'),
                        'pay_day' => $nextPayDay->format('Y-m-d'),
                        'days_until_pay' => max(0, ceil($today->diffInDays($nextPayDay, true)))
                    ]
                ]
            ]
        ]);
    }

    /**
     * Get all pay days (previous, current, and upcoming)
     */
    public function getPayDays()
    {
        $firstPayDay = TimezoneUtil::parse('2025-07-24'); // First pay day (Thursday)
        $today = TimezoneUtil::now();
        
        // Calculate how many pay periods have passed since the first pay day
        $weeksSinceFirstPay = $today->diffInWeeks($firstPayDay, false);
        $payPeriodsPassed = floor($weeksSinceFirstPay / 2);
        
        // Current pay day is the first pay day minus the passed pay periods
        $currentPayDay = $firstPayDay->copy()->subWeeks($payPeriodsPassed * 2);
        
        $payDays = [];
        
        // Add previous pay days (last 6 pay periods)
        for ($i = 1; $i <= 6; $i++) {
            $payDay = $currentPayDay->copy()->subWeeks($i * 2);
            $payDays[] = [
                'date' => $payDay->format('Y-m-d'),
                'label' => $payDay->format('l, M j, Y'),
                'type' => 'previous'
            ];
        }
        
        // Add current pay day
        $payDays[] = [
            'date' => $currentPayDay->format('Y-m-d'),
            'label' => $currentPayDay->format('l, M j, Y'),
            'type' => 'current'
        ];
        
        // Add next pay day
        $nextPayDay = $currentPayDay->copy()->addWeeks(2);
        $payDays[] = [
            'date' => $nextPayDay->format('Y-m-d'),
            'label' => $nextPayDay->format('l, M j, Y'),
            'type' => 'upcoming'
        ];
        
        // Sort by date (oldest first)
        usort($payDays, function($a, $b) {
            return strcmp($a['date'], $b['date']);
        });
        
        return response()->json([
            'success' => true,
            'data' => $payDays
        ]);
    }

    /**
     * Generate work hour report PDF
     */
    public function generateWorkHourReport(Request $request)
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'pay_day' => 'required|date',
            'employee_ids' => 'required|array',
            'employee_ids.*' => 'exists:employees,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $payDay = TimezoneUtil::parse($request->pay_day);
        $employeeIds = $request->employee_ids;
        
        // Calculate work period for the selected pay day
        $periodStart = $payDay->copy()->subDays(20);
        $periodEnd = $payDay->copy()->subDays(7);
        
        // Get selected employees
        $employees = Employee::whereIn('id', $employeeIds)->get();
        
        $reportData = [];
        
        foreach ($employees as $employee) {
            // Get work hours for the period
            $workHours = $employee->workHours()
                ->whereBetween('date', [$periodStart->format('Y-m-d'), $periodEnd->format('Y-m-d')])
                ->get();
            
            $totalHours = $workHours->sum('total_hours');
            $totalEarnings = $totalHours * $employee->hourly_rate;
            
            $reportData[] = [
                'name' => $employee->full_legal_name,
                'position' => $employee->position,
                'sin_number' => $employee->sin_number,
                'address' => $employee->address,
                'postal_code' => $employee->postal_code,
                'country' => $employee->country,
                'total_hours' => round($totalHours, 2),
                'hourly_rate' => $employee->hourly_rate,
                'total_earnings' => round($totalEarnings, 2)
            ];
        }
        
        // Generate PDF using a simple HTML template
        $html = view('reports.work-hour-report', [
            'payDay' => $payDay->format('l, M j, Y'),
            'periodStart' => $periodStart->format('M j, Y'),
            'periodEnd' => $periodEnd->format('M j, Y'),
            'employees' => $reportData,
            'generatedAt' => TimezoneUtil::formatNow()
        ])->render();
        
        // For now, return the data as JSON. In production, you'd use a PDF library like DomPDF
        return response()->json([
            'success' => true,
            'data' => [
                'pay_day' => $payDay->format('Y-m-d'),
                'period_start' => $periodStart->format('Y-m-d'),
                'period_end' => $periodEnd->format('Y-m-d'),
                'employees' => $reportData,
                'html' => $html
            ]
        ]);
    }
}
