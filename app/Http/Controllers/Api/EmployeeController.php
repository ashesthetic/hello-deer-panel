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
     * Get all employees with their total work hours
     */
    public function employeesWithHours()
    {
        $employees = Employee::with('workHours')
            ->orderBy('full_legal_name')
            ->get();

        $employeeData = $employees->map(function ($employee) {
            $totalHours = $employee->workHours->sum('total_hours');
            $totalDays = $employee->workHours->groupBy('date')->count();
            $avgHoursPerDay = $totalDays > 0 ? round($totalHours / $totalDays, 2) : 0;
            $totalEarnings = round($totalHours * $employee->hourly_rate, 2);
            
            // Calculate total due as the difference between earnings and what's been paid
            $totalDue = max(0, $totalEarnings - $employee->total_paid);

            return [
                'id' => $employee->id,
                'full_legal_name' => $employee->full_legal_name,
                'preferred_name' => $employee->preferred_name,
                'position' => $employee->position,
                'department' => $employee->department,
                'hire_date' => $employee->hire_date,
                'status' => $employee->status,
                'hourly_rate' => $employee->hourly_rate,
                'total_hours' => round($totalHours, 2),
                'total_work_days' => $totalDays,
                'avg_hours_per_day' => $avgHoursPerDay,
                'total_earnings' => $totalEarnings,
                'total_paid' => round($employee->total_paid, 2),
                'total_due' => round($totalDue, 2),
                'resolved_hours' => round($employee->resolved_hours, 2),
                'unpaid_hours' => round($totalHours - $employee->resolved_hours, 2)
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $employeeData
        ]);
    }

    /**
     * Resolve hours for a specific employee
     */
    public function resolveHours(Request $request, Employee $employee)
    {
        $request->validate([
            'resolved_hours' => 'required|numeric|min:0',
            'notes' => 'nullable|string'
        ]);

        $resolvedHours = $request->resolved_hours;
        $previousResolved = $employee->resolved_hours;
        
        // Calculate the new payment based on additional resolved hours
        $additionalHours = $resolvedHours - $previousResolved;
        $additionalPayment = $additionalHours * $employee->hourly_rate;
        
        // Calculate new total paid amount
        $newTotalPaid = $employee->total_paid + $additionalPayment;
        
        // Calculate total earnings based on all hours worked
        $totalHours = $employee->workHours->sum('total_hours');
        $totalEarnings = $totalHours * $employee->hourly_rate;
        
        // Calculate what's still due (total earnings minus what's been paid)
        $totalDue = max(0, $totalEarnings - $newTotalPaid);
        
        // Update employee data
        $employee->update([
            'resolved_hours' => $resolvedHours,
            'total_paid' => $newTotalPaid,
            'total_due' => $totalDue,
            'unpaid_hours' => max(0, $totalHours - $resolvedHours)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Hours resolved successfully',
            'data' => [
                'employee_id' => $employee->id,
                'resolved_hours' => $resolvedHours,
                'additional_payment' => round($additionalPayment, 2),
                'total_paid' => round($newTotalPaid, 2),
                'total_due' => round($totalDue, 2),
                'unpaid_hours' => round($totalHours - $resolvedHours, 2),
                'total_earnings' => round($totalEarnings, 2)
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
        $today = TimezoneUtil::now();
        $payDays = [];
        
        // Semi-monthly pay schedule:
        // Pay Period 1st-15th -> Pay Date 22nd of same month
        // Pay Period 16th-last day -> Pay Date 7th of next month
        
        // Generate pay days for the last 6 months and next month
        $startMonth = $today->copy()->subMonths(6);
        $endMonth = $today->copy()->addMonths(1);
        
        $currentMonth = $startMonth->copy()->startOfMonth();
        
        while ($currentMonth->lessThanOrEqualTo($endMonth)) {
            $year = $currentMonth->year;
            $month = $currentMonth->month;
            
            // First pay date of the month (22nd) - pays for 1st-15th of same month
            $payDate22 = TimezoneUtil::parse("{$year}-{$month}-22");
            
            // Second pay date of the month (7th) - pays for 16th-last day of previous month
            $payDate7 = TimezoneUtil::parse("{$year}-{$month}-07");
            
            // Determine if each pay date is previous, current, or upcoming
            $type22 = 'previous';
            $type7 = 'previous';
            
            // A pay date is "current" if today is within its pay period or between period end and pay date
            // For pay date 22nd: period is 1st-15th, so current if today is between 1st and 22nd
            if ($today->year == $year && $today->month == $month && $today->day >= 1 && $today->day <= 22) {
                $type22 = 'current';
            } elseif ($payDate22->greaterThan($today)) {
                $type22 = 'upcoming';
            }
            
            // For pay date 7th: period is 16th-last day of previous month
            $prevMonth = $currentMonth->copy()->subMonth();
            if ($today->year == $prevMonth->year && $today->month == $prevMonth->month && $today->day >= 16) {
                $type7 = 'current';
            } elseif ($today->year == $year && $today->month == $month && $today->day <= 7) {
                $type7 = 'current';
            } elseif ($payDate7->greaterThan($today)) {
                $type7 = 'upcoming';
            }
            
            $payDays[] = [
                'date' => $payDate7->format('Y-m-d'),
                'label' => $payDate7->format('M j, Y') . ' (Period: ' . $prevMonth->format('M') . ' 16-' . $prevMonth->daysInMonth . ')',
                'type' => $type7
            ];
            
            $payDays[] = [
                'date' => $payDate22->format('Y-m-d'),
                'label' => $payDate22->format('M j, Y') . ' (Period: ' . $currentMonth->format('M') . ' 1-15)',
                'type' => $type22
            ];
            
            $currentMonth->addMonth();
        }
        
        // Sort by date (oldest first)
        usort($payDays, function($a, $b) {
            return strcmp($a['date'], $b['date']);
        });
        
        // Keep only reasonable range: last 6 pay periods + current + next
        $currentIndex = -1;
        for ($i = 0; $i < count($payDays); $i++) {
            if ($payDays[$i]['type'] === 'current') {
                $currentIndex = $i;
                break;
            }
        }
        
        if ($currentIndex >= 0) {
            $start = max(0, $currentIndex - 6);
            $end = min(count($payDays) - 1, $currentIndex + 1);
            $payDays = array_slice($payDays, $start, $end - $start + 1);
        }
        
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
        
        // Calculate work period based on semi-monthly pay schedule
        // Pay date 22nd -> Period: 1st-15th of same month
        // Pay date 7th -> Period: 16th-last day of previous month
        
        $payDayOfMonth = (int) $payDay->format('d');
        
        if ($payDayOfMonth == 22) {
            // Period: 1st to 15th of the same month
            $periodStart = $payDay->copy()->startOfMonth();
            $periodEnd = $payDay->copy()->setDay(15);
        } elseif ($payDayOfMonth == 7) {
            // Period: 16th to last day of previous month
            $prevMonth = $payDay->copy()->subMonth();
            $periodStart = $prevMonth->copy()->setDay(16);
            $periodEnd = $prevMonth->copy()->endOfMonth();
        } else {
            // Fallback for custom dates: assume 22nd if in first half, 7th if in second half
            if ($payDayOfMonth <= 15) {
                // Treat as 7th: previous month 16th to last day
                $prevMonth = $payDay->copy()->subMonth();
                $periodStart = $prevMonth->copy()->setDay(16);
                $periodEnd = $prevMonth->copy()->endOfMonth();
            } else {
                // Treat as 22nd: current month 1st to 15th
                $periodStart = $payDay->copy()->startOfMonth();
                $periodEnd = $payDay->copy()->setDay(15);
            }
        }
        
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

    /**
     * Generate pay stubs with calculations
     */
    public function generatePayStubs(Request $request)
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
        
        // Calculate work period based on semi-monthly pay schedule
        // Pay date 22nd -> Period: 1st-15th of same month
        // Pay date 7th -> Period: 16th-last day of previous month
        
        $payDayOfMonth = (int) $payDay->format('d');
        
        if ($payDayOfMonth == 22) {
            // Period: 1st to 15th of the same month
            $periodStart = $payDay->copy()->startOfMonth();
            $periodEnd = $payDay->copy()->setDay(15);
        } elseif ($payDayOfMonth == 7) {
            // Period: 16th to last day of previous month
            $prevMonth = $payDay->copy()->subMonth();
            $periodStart = $prevMonth->copy()->setDay(16);
            $periodEnd = $prevMonth->copy()->endOfMonth();
        } else {
            // Fallback for custom dates: assume 22nd if in first half, 7th if in second half
            if ($payDayOfMonth <= 15) {
                // Treat as 7th: previous month 16th to last day
                $prevMonth = $payDay->copy()->subMonth();
                $periodStart = $prevMonth->copy()->setDay(16);
                $periodEnd = $prevMonth->copy()->endOfMonth();
            } else {
                // Treat as 22nd: current month 1st to 15th
                $periodStart = $payDay->copy()->startOfMonth();
                $periodEnd = $payDay->copy()->setDay(15);
            }
        }
        
        // Calculate expected work days in period for overtime threshold
        // Approximate: 40 hours/week * 2 weeks = 80 hours for semi-monthly
        $daysInPeriod = $periodStart->diffInDays($periodEnd) + 1;
        $expectedWeeksInPeriod = $daysInPeriod / 7;
        $regularHoursThreshold = $expectedWeeksInPeriod * 40; // 40 hours per week
        
        // Get selected employees
        $employees = Employee::whereIn('id', $employeeIds)->get();
        
        $payStubsData = [];
        
        foreach ($employees as $employee) {
            // Get work hours for the period
            $workHours = $employee->workHours()
                ->whereBetween('date', [$periodStart->format('Y-m-d'), $periodEnd->format('Y-m-d')])
                ->get();
            
            $totalHours = $workHours->sum('total_hours');
            $hourlyRate = $employee->hourly_rate;
            
            // Calculate earnings
            $regularHours = min($totalHours, $regularHoursThreshold); // Use dynamic regular hours threshold based on period length
            $overtimeHours = max(0, $totalHours - $regularHoursThreshold);
            $regularPay = $regularHours * $hourlyRate;
            $overtimePay = $overtimeHours * ($hourlyRate * 1.5); // 1.5x for overtime
            
            // Stat holiday and vacation calculations (simplified)
            $statHolidayHours = 0; // This would be calculated based on actual stat holidays
            $statHolidayPay = $statHolidayHours * $hourlyRate;
            
            $vacationPay = ($regularPay + $overtimePay) * 0.04; // 4% vacation pay
            $vacationPaid = 0; // This would be tracked separately
            
            $totalEarnings = $regularPay + $overtimePay + $statHolidayPay + $vacationPay;
            
            // Calculate YTD totals (simplified - would need to track this in database)
            $ytdEarnings = $totalEarnings * 24; // Assuming 24 pay periods per year for semi-monthly (12 months * 2 periods)
            $ytdCPP = 0;
            $ytdEI = 0;
            $ytdFederalTax = 0;
            
            // Calculate deductions
            $cppRate = 0.0595; // 5.95%
            $cppBasicExemption = 3500;
            $cppMaxEarnings = 68500;
            $cppContribution = min(max(0, $totalEarnings - ($cppBasicExemption / 24)), $cppMaxEarnings / 24) * $cppRate;
            
            $eiRate = 0.0166; // 1.66%
            $eiMaxInsurableEarnings = 63200;
            $eiContribution = min($totalEarnings, $eiMaxInsurableEarnings / 24) * $eiRate;
            
            // Federal income tax (simplified calculation)
            $federalTax = $this->calculateFederalTax($totalEarnings * 24); // Annualized for tax calculation
            $federalTaxPerPeriod = $federalTax / 24;
            
            $totalDeductions = $cppContribution + $eiContribution + $federalTaxPerPeriod;
            $netPay = $totalEarnings - $totalDeductions;
            
            // Vacation summary
            $vacationEarned = $totalEarnings * 0.04; // 4% of earnings
            $vacationPaid = 0; // Would be tracked separately
            
            $payStubsData[] = [
                'employee_id' => $employee->id,
                'name' => $employee->full_legal_name,
                'position' => $employee->position,
                'sin_number' => $employee->sin_number,
                'address' => $employee->address,
                'postal_code' => $employee->postal_code,
                'country' => $employee->country,
                'hourly_rate' => $hourlyRate,
                'pay_day' => $payDay->format('Y-m-d'),
                'period_start' => $periodStart->format('Y-m-d'),
                'period_end' => $periodEnd->format('Y-m-d'),
                'earnings' => [
                    'regular' => [
                        'hours' => $regularHours,
                        'rate' => $hourlyRate,
                        'current_amount' => round($regularPay, 2),
                        'ytd_amount' => round($regularPay * 24, 2)
                    ],
                    'stat_holiday_paid' => [
                        'hours' => $statHolidayHours,
                        'rate' => $hourlyRate,
                        'current_amount' => round($statHolidayPay, 2),
                        'ytd_amount' => round($statHolidayPay * 24, 2)
                    ],
                    'overtime' => [
                        'hours' => $overtimeHours,
                        'rate' => $hourlyRate * 1.5,
                        'current_amount' => round($overtimePay, 2),
                        'ytd_amount' => round($overtimePay * 24, 2)
                    ],
                    'vac_paid' => [
                        'hours' => 0,
                        'rate' => $hourlyRate,
                        'current_amount' => round($vacationPaid, 2),
                        'ytd_amount' => round($vacationPaid * 24, 2)
                    ],
                    'total' => [
                        'hours' => $totalHours,
                        'rate' => $hourlyRate,
                        'current_amount' => round($totalEarnings, 2),
                        'ytd_amount' => round($ytdEarnings, 2)
                    ]
                ],
                'deductions' => [
                    'cpp_employee' => [
                        'current_amount' => round($cppContribution, 2),
                        'ytd_amount' => round($ytdCPP, 2)
                    ],
                    'ei_employee' => [
                        'current_amount' => round($eiContribution, 2),
                        'ytd_amount' => round($ytdEI, 2)
                    ],
                    'federal_income_tax' => [
                        'current_amount' => round($federalTaxPerPeriod, 2),
                        'ytd_amount' => round($ytdFederalTax, 2)
                    ],
                    'total' => [
                        'current_amount' => round($totalDeductions, 2),
                        'ytd_amount' => round($ytdCPP + $ytdEI + $ytdFederalTax, 2)
                    ]
                ],
                'net_pay' => round($netPay, 2),
                'vacation_summary' => [
                    'vac_earned' => round($vacationEarned, 2),
                    'vac_earned_ytd' => round($vacationEarned * 24, 2),
                    'vac_paid' => round($vacationPaid, 2),
                    'vac_paid_ytd' => round($vacationPaid * 24, 2)
                ]
            ];
        }
        
        // Generate HTML for PDF
        $html = view('reports.pay-stubs', [
            'payDay' => $payDay->format('l, M j, Y'),
            'periodStart' => $periodStart->format('M j, Y'),
            'periodEnd' => $periodEnd->format('M j, Y'),
            'payStubs' => $payStubsData,
            'generatedAt' => TimezoneUtil::formatNow()
        ])->render();
        
        return response()->json([
            'success' => true,
            'data' => [
                'pay_day' => $payDay->format('Y-m-d'),
                'period_start' => $periodStart->format('Y-m-d'),
                'period_end' => $periodEnd->format('Y-m-d'),
                'pay_stubs' => $payStubsData,
                'html' => $html
            ]
        ]);
    }

    /**
     * Calculate federal income tax (simplified)
     */
    private function calculateFederalTax($annualIncome)
    {
        // 2025 Federal Tax Brackets (simplified)
        if ($annualIncome <= 53359) {
            return $annualIncome * 0.15;
        } elseif ($annualIncome <= 106717) {
            return 53359 * 0.15 + ($annualIncome - 53359) * 0.205;
        } elseif ($annualIncome <= 165430) {
            return 53359 * 0.15 + (106717 - 53359) * 0.205 + ($annualIncome - 106717) * 0.26;
        } elseif ($annualIncome <= 235675) {
            return 53359 * 0.15 + (106717 - 53359) * 0.205 + (165430 - 106717) * 0.26 + ($annualIncome - 165430) * 0.29;
        } else {
            return 53359 * 0.15 + (106717 - 53359) * 0.205 + (165430 - 106717) * 0.26 + (235675 - 165430) * 0.29 + ($annualIncome - 235675) * 0.33;
        }
    }

    /**
     * Generate editable pay stubs with form fields
     */
    public function generatePayStubsEditable(Request $request)
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
        
        // Calculate work period based on semi-monthly pay schedule
        // Pay date 22nd -> Period: 1st-15th of same month
        // Pay date 7th -> Period: 16th-last day of previous month
        
        $payDayOfMonth = (int) $payDay->format('d');
        
        if ($payDayOfMonth == 22) {
            // Period: 1st to 15th of the same month
            $periodStart = $payDay->copy()->startOfMonth();
            $periodEnd = $payDay->copy()->setDay(15);
        } elseif ($payDayOfMonth == 7) {
            // Period: 16th to last day of previous month
            $prevMonth = $payDay->copy()->subMonth();
            $periodStart = $prevMonth->copy()->setDay(16);
            $periodEnd = $prevMonth->copy()->endOfMonth();
        } else {
            // Fallback for custom dates: assume 22nd if in first half, 7th if in second half
            if ($payDayOfMonth <= 15) {
                // Treat as 7th: previous month 16th to last day
                $prevMonth = $payDay->copy()->subMonth();
                $periodStart = $prevMonth->copy()->setDay(16);
                $periodEnd = $prevMonth->copy()->endOfMonth();
            } else {
                // Treat as 22nd: current month 1st to 15th
                $periodStart = $payDay->copy()->startOfMonth();
                $periodEnd = $payDay->copy()->setDay(15);
            }
        }
        
        // Calculate expected work days in period for overtime threshold
        $daysInPeriod = $periodStart->diffInDays($periodEnd) + 1;
        $expectedWeeksInPeriod = $daysInPeriod / 7;
        $regularHoursThreshold = $expectedWeeksInPeriod * 40; // 40 hours per week
        
        // Get selected employees
        $employees = Employee::whereIn('id', $employeeIds)->get();
        
        $payStubsData = [];
        
        foreach ($employees as $employee) {
            // Get work hours for the period
            $workHours = $employee->workHours()
                ->whereBetween('date', [$periodStart->format('Y-m-d'), $periodEnd->format('Y-m-d')])
                ->get();
            
            $totalHours = $workHours->sum('total_hours');
            $hourlyRate = $employee->hourly_rate;
            
            // Calculate earnings
            $regularHours = min($totalHours, $regularHoursThreshold); // Use dynamic regular hours threshold based on period length (40 hours/week * 2 weeks)
            $overtimeHours = max(0, $totalHours - $regularHoursThreshold);
            $regularPay = $regularHours * $hourlyRate;
            $overtimePay = $overtimeHours * ($hourlyRate * 1.5); // 1.5x for overtime
            
            // Stat holiday and vacation calculations (simplified)
            $statHolidayHours = 0; // This would be calculated based on actual stat holidays
            $statHolidayPay = $statHolidayHours * $hourlyRate;
            
            $vacationPay = ($regularPay + $overtimePay) * 0.04; // 4% vacation pay
            $vacationPaid = 0; // This would be tracked separately
            
            $totalEarnings = $regularPay + $overtimePay + $statHolidayPay + $vacationPay;
            
            // Calculate YTD totals (simplified - would need to track this in database)
            $ytdEarnings = $totalEarnings * 24; // Assuming 24 pay periods per year for semi-monthly (12 months * 2 periods)
            $ytdCPP = 0;
            $ytdEI = 0;
            $ytdFederalTax = 0;
            
            // Calculate deductions
            $cppRate = 0.0595; // 5.95%
            $cppBasicExemption = 3500;
            $cppMaxEarnings = 68500;
            $cppContribution = min(max(0, $totalEarnings - ($cppBasicExemption / 24)), $cppMaxEarnings / 24) * $cppRate;
            
            $eiRate = 0.0166; // 1.66%
            $eiMaxInsurableEarnings = 63200;
            $eiContribution = min($totalEarnings, $eiMaxInsurableEarnings / 24) * $eiRate;
            
            // Federal income tax (simplified calculation)
            $federalTax = $this->calculateFederalTax($totalEarnings * 24); // Annualized for tax calculation
            $federalTaxPerPeriod = $federalTax / 24;
            
            $totalDeductions = $cppContribution + $eiContribution + $federalTaxPerPeriod;
            $netPay = $totalEarnings - $totalDeductions;
            
            // Vacation summary
            $vacationEarned = $totalEarnings * 0.04; // 4% of earnings
            $vacationPaid = 0; // Would be tracked separately
            
            $payStubsData[] = [
                'employee_id' => $employee->id,
                'name' => $employee->full_legal_name,
                'position' => $employee->position,
                'sin_number' => $employee->sin_number,
                'address' => $employee->address,
                'postal_code' => $employee->postal_code,
                'country' => $employee->country,
                'hourly_rate' => $hourlyRate,
                'pay_day' => $payDay->format('Y-m-d'),
                'period_start' => $periodStart->format('Y-m-d'),
                'period_end' => $periodEnd->format('Y-m-d'),
                'earnings' => [
                    'regular' => [
                        'hours' => $regularHours,
                        'rate' => $hourlyRate,
                        'current_amount' => round($regularPay, 2),
                        'ytd_amount' => round($regularPay * 24, 2)
                    ],
                    'stat_holiday_paid' => [
                        'hours' => $statHolidayHours,
                        'rate' => $hourlyRate,
                        'current_amount' => round($statHolidayPay, 2),
                        'ytd_amount' => round($statHolidayPay * 24, 2)
                    ],
                    'overtime' => [
                        'hours' => $overtimeHours,
                        'rate' => $hourlyRate * 1.5,
                        'current_amount' => round($overtimePay, 2),
                        'ytd_amount' => round($overtimePay * 24, 2)
                    ],
                    'vac_paid' => [
                        'hours' => 0,
                        'rate' => $hourlyRate,
                        'current_amount' => round($vacationPaid, 2),
                        'ytd_amount' => round($vacationPaid * 24, 2)
                    ],
                    'total' => [
                        'hours' => $totalHours,
                        'rate' => $hourlyRate,
                        'current_amount' => round($totalEarnings, 2),
                        'ytd_amount' => round($ytdEarnings, 2)
                    ]
                ],
                'deductions' => [
                    'cpp_employee' => [
                        'current_amount' => round($cppContribution, 2),
                        'ytd_amount' => round($ytdCPP, 2)
                    ],
                    'ei_employee' => [
                        'current_amount' => round($eiContribution, 2),
                        'ytd_amount' => round($ytdEI, 2)
                    ],
                    'federal_income_tax' => [
                        'current_amount' => round($federalTaxPerPeriod, 2),
                        'ytd_amount' => round($ytdFederalTax, 2)
                    ],
                    'total' => [
                        'current_amount' => round($totalDeductions, 2),
                        'ytd_amount' => round($ytdCPP + $ytdEI + $ytdFederalTax, 2)
                    ]
                ],
                'net_pay' => round($netPay, 2),
                'vacation_summary' => [
                    'vac_earned' => round($vacationEarned, 2),
                    'vac_earned_ytd' => round($vacationEarned * 24, 2),
                    'vac_paid' => round($vacationPaid, 2),
                    'vac_paid_ytd' => round($vacationPaid * 24, 2)
                ]
            ];
        }
        
        // Generate HTML for editable form
        $html = view('reports.pay-stubs-editable', [
            'payDay' => $payDay->format('l, M j, Y'),
            'periodStart' => $periodStart->format('M j, Y'),
            'periodEnd' => $periodEnd->format('M j, Y'),
            'payStubs' => $payStubsData,
            'generatedAt' => TimezoneUtil::formatNow()
        ])->render();
        
        return response()->json([
            'success' => true,
            'data' => [
                'pay_day' => $payDay->format('Y-m-d'),
                'period_start' => $periodStart->format('Y-m-d'),
                'period_end' => $periodEnd->format('Y-m-d'),
                'pay_stubs' => $payStubsData,
                'html' => $html
            ]
        ]);
    }
}
