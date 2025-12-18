<?php

namespace App\Http\Controllers;

use App\Models\Payroll;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;
use Carbon\Carbon;

class PayrollController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $sortBy = $request->input('sort_by', 'pay_date');
        $sortDirection = $request->input('sort_direction', 'desc');
        $employeeId = $request->input('employee_id');
        
        // Build query
        $query = Payroll::with('employee');
        
        // Filter by employee if specified
        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }
        
        // Handle sorting
        $allowedSortFields = ['id', 'pay_date', 'employee_id', 'net_pay', 'payment_date', 'created_at', 'updated_at'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'pay_date';
        }
        
        $query->orderBy($sortBy, $sortDirection);
        
        $payrolls = $query->paginate($perPage);
        
        return response()->json($payrolls);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pay_date' => 'required|date',
            'pay_period' => 'nullable|string|max:255',
            'employee_id' => 'required|exists:employees,id',
            'regular_hours' => 'nullable|numeric|min:0',
            'regular_rate' => 'nullable|numeric|min:0',
            'regular_current' => 'nullable|numeric|min:0',
            'regular_ytd' => 'nullable|numeric|min:0',
            'stat_hours' => 'nullable|numeric|min:0',
            'stat_rate' => 'nullable|numeric|min:0',
            'stat_current' => 'nullable|numeric|min:0',
            'stat_ytd' => 'nullable|numeric|min:0',
            'overtime_hours' => 'nullable|numeric|min:0',
            'overtime_rate' => 'nullable|numeric|min:0',
            'overtime_current' => 'nullable|numeric|min:0',
            'overtime_ytd' => 'nullable|numeric|min:0',
            'total_hours' => 'nullable|numeric|min:0',
            'total_current' => 'nullable|numeric|min:0',
            'total_ytd' => 'nullable|numeric|min:0',
            'cpp_emp_current' => 'nullable|numeric|min:0',
            'cpp_emp_ytd' => 'nullable|numeric|min:0',
            'ei_emp_current' => 'nullable|numeric|min:0',
            'ei_emp_ytd' => 'nullable|numeric|min:0',
            'fit_current' => 'nullable|numeric|min:0',
            'fit_ytd' => 'nullable|numeric|min:0',
            'total_deduction_current' => 'nullable|numeric|min:0',
            'total_deduction_ytd' => 'nullable|numeric|min:0',
            'vac_earned_current' => 'nullable|numeric|min:0',
            'vac_earned_ytd' => 'nullable|numeric|min:0',
            'vac_paid_current' => 'nullable|numeric|min:0',
            'vac_paid_ytd' => 'nullable|numeric|min:0',
            'net_pay' => 'nullable|numeric|min:0',
            'payment_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $payroll = Payroll::create($request->all());

        return response()->json([
            'message' => 'Payroll created successfully',
            'data' => $payroll->load('employee')
        ], 201);
    }

    /**
     * Store multiple payroll records at once.
     */
    public function bulkStore(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payrolls' => 'required|array|min:1',
            'payrolls.*.pay_date' => 'required|date',
            'payrolls.*.pay_period' => 'nullable|string|max:255',
            'payrolls.*.employee_id' => 'required|exists:employees,id',
            'payrolls.*.regular_hours' => 'nullable|numeric|min:0',
            'payrolls.*.regular_rate' => 'nullable|numeric|min:0',
            'payrolls.*.regular_current' => 'nullable|numeric|min:0',
            'payrolls.*.regular_ytd' => 'nullable|numeric|min:0',
            'payrolls.*.stat_hours' => 'nullable|numeric|min:0',
            'payrolls.*.stat_rate' => 'nullable|numeric|min:0',
            'payrolls.*.stat_current' => 'nullable|numeric|min:0',
            'payrolls.*.stat_ytd' => 'nullable|numeric|min:0',
            'payrolls.*.overtime_hours' => 'nullable|numeric|min:0',
            'payrolls.*.overtime_rate' => 'nullable|numeric|min:0',
            'payrolls.*.overtime_current' => 'nullable|numeric|min:0',
            'payrolls.*.overtime_ytd' => 'nullable|numeric|min:0',
            'payrolls.*.total_hours' => 'nullable|numeric|min:0',
            'payrolls.*.total_current' => 'nullable|numeric|min:0',
            'payrolls.*.total_ytd' => 'nullable|numeric|min:0',
            'payrolls.*.cpp_emp_current' => 'nullable|numeric|min:0',
            'payrolls.*.cpp_emp_ytd' => 'nullable|numeric|min:0',
            'payrolls.*.ei_emp_current' => 'nullable|numeric|min:0',
            'payrolls.*.ei_emp_ytd' => 'nullable|numeric|min:0',
            'payrolls.*.fit_current' => 'nullable|numeric|min:0',
            'payrolls.*.fit_ytd' => 'nullable|numeric|min:0',
            'payrolls.*.total_deduction_current' => 'nullable|numeric|min:0',
            'payrolls.*.total_deduction_ytd' => 'nullable|numeric|min:0',
            'payrolls.*.vac_earned_current' => 'nullable|numeric|min:0',
            'payrolls.*.vac_earned_ytd' => 'nullable|numeric|min:0',
            'payrolls.*.vac_paid_current' => 'nullable|numeric|min:0',
            'payrolls.*.vac_paid_ytd' => 'nullable|numeric|min:0',
            'payrolls.*.net_pay' => 'nullable|numeric|min:0',
            'payrolls.*.payment_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $created = [];
        foreach ($request->input('payrolls') as $payrollData) {
            $payroll = Payroll::create($payrollData);
            $created[] = $payroll->load('employee');
        }

        return response()->json([
            'message' => count($created) . ' payroll record(s) created successfully',
            'data' => $created
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $payroll = Payroll::with('employee')->findOrFail($id);
        
        return response()->json([
            'data' => $payroll
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $payroll = Payroll::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'pay_date' => 'sometimes|required|date',
            'pay_period' => 'nullable|string|max:255',
            'employee_id' => 'sometimes|required|exists:users,id',
            'regular_hours' => 'nullable|numeric|min:0',
            'regular_rate' => 'nullable|numeric|min:0',
            'regular_current' => 'nullable|numeric|min:0',
            'regular_ytd' => 'nullable|numeric|min:0',
            'stat_hours' => 'nullable|numeric|min:0',
            'stat_rate' => 'nullable|numeric|min:0',
            'stat_current' => 'nullable|numeric|min:0',
            'stat_ytd' => 'nullable|numeric|min:0',
            'overtime_hours' => 'nullable|numeric|min:0',
            'overtime_rate' => 'nullable|numeric|min:0',
            'overtime_current' => 'nullable|numeric|min:0',
            'overtime_ytd' => 'nullable|numeric|min:0',
            'total_hours' => 'nullable|numeric|min:0',
            'total_current' => 'nullable|numeric|min:0',
            'total_ytd' => 'nullable|numeric|min:0',
            'cpp_emp_current' => 'nullable|numeric|min:0',
            'cpp_emp_ytd' => 'nullable|numeric|min:0',
            'ei_emp_current' => 'nullable|numeric|min:0',
            'ei_emp_ytd' => 'nullable|numeric|min:0',
            'fit_current' => 'nullable|numeric|min:0',
            'fit_ytd' => 'nullable|numeric|min:0',
            'total_deduction_current' => 'nullable|numeric|min:0',
            'total_deduction_ytd' => 'nullable|numeric|min:0',
            'vac_earned_current' => 'nullable|numeric|min:0',
            'vac_earned_ytd' => 'nullable|numeric|min:0',
            'vac_paid_current' => 'nullable|numeric|min:0',
            'vac_paid_ytd' => 'nullable|numeric|min:0',
            'net_pay' => 'nullable|numeric|min:0',
            'payment_date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $payroll->update($request->all());

        return response()->json([
            'message' => 'Payroll updated successfully',
            'data' => $payroll->load('employee')
        ]);
    }

    /**
     * Remove the specified resource from storage (soft delete).
     */
    public function destroy(string $id)
    {
        $payroll = Payroll::findOrFail($id);
        $payroll->delete();

        return response()->json([
            'message' => 'Payroll deleted successfully'
        ]);
    }

    /**
     * Get all payrolls including soft deleted ones.
     */
    public function withTrashed(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $sortBy = $request->input('sort_by', 'pay_date');
        $sortDirection = $request->input('sort_direction', 'desc');
        $employeeId = $request->input('employee_id');
        
        $query = Payroll::withTrashed()->with('employee');
        
        // Filter by employee if specified
        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }
        
        $allowedSortFields = ['id', 'pay_date', 'employee_id', 'net_pay', 'payment_date', 'created_at', 'updated_at', 'deleted_at'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'pay_date';
        }
        
        $query->orderBy($sortBy, $sortDirection);
        
        $payrolls = $query->paginate($perPage);
        
        return response()->json($payrolls);
    }

    /**
     * Restore a soft deleted payroll.
     */
    public function restore(string $id)
    {
        $payroll = Payroll::withTrashed()->findOrFail($id);
        
        if (!$payroll->trashed()) {
            return response()->json([
                'message' => 'Payroll is not deleted'
            ], 400);
        }
        
        $payroll->restore();

        return response()->json([
            'message' => 'Payroll restored successfully',
            'data' => $payroll->load('employee')
        ]);
    }

    /**
     * Permanently delete a payroll.
     */
    public function forceDelete(string $id)
    {
        $payroll = Payroll::withTrashed()->findOrFail($id);
        $payroll->forceDelete();

        return response()->json([
            'message' => 'Payroll permanently deleted'
        ]);
    }

    /**
     * Get payroll summary by employee.
     */
    public function summaryByEmployee(string $employeeId)
    {
        $employee = User::findOrFail($employeeId);
        
        $summary = [
            'employee' => $employee,
            'total_payrolls' => Payroll::where('employee_id', $employeeId)->count(),
            'current_year_total' => Payroll::where('employee_id', $employeeId)
                ->whereYear('pay_date', date('Y'))
                ->sum('net_pay'),
            'latest_payroll' => Payroll::where('employee_id', $employeeId)
                ->orderBy('pay_date', 'desc')
                ->first(),
        ];
        
        return response()->json($summary);
    }

    /**
     * Email pay stub to employee
     */
    public function emailPayStub(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            'pdf_data' => 'required|string', // Base64 encoded PDF
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Get the payroll record with employee
        $payroll = Payroll::with('employee')->find($id);

        if (!$payroll) {
            return response()->json([
                'success' => false,
                'message' => 'Payroll record not found'
            ], 404);
        }

        $employee = $payroll->employee;

        if (!$employee) {
            return response()->json([
                'success' => false,
                'message' => 'Employee not found'
            ], 404);
        }

        if (!$employee->email) {
            return response()->json([
                'success' => false,
                'message' => 'Employee does not have an email address'
            ], 400);
        }

        // Decode PDF data
        $pdfData = base64_decode($request->pdf_data);
        
        // Format pay period for email subject
        $payDate = Carbon::parse($payroll->pay_date);
        $payPeriod = $payroll->pay_period ?: $payDate->format('M d, Y');

        try {
            Mail::raw("Hello {$employee->preferred_name},\n\nPlease find attached your pay stub for the pay period: {$payPeriod}.\n\nBest regards,\nHello Deer!", function ($message) use ($employee, $payPeriod, $pdfData) {
                $message->to($employee->email)
                    ->subject("Pay Stub - {$payPeriod}")
                    ->from(env('MAIL_FROM_ADDRESS', 'noreply@example.com'), 'Hello Deer!')
                    ->attachData($pdfData, 'pay-stub.pdf', [
                        'mime' => 'application/pdf',
                    ]);
            });

            return response()->json([
                'success' => true,
                'message' => "Pay stub sent to {$employee->email}",
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to send pay stub email to ' . $employee->email . ': ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to send email: ' . $e->getMessage()
            ], 500);
        }
    }
}
