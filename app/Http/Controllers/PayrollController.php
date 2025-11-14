<?php

namespace App\Http\Controllers;

use App\Models\Payroll;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PayrollController extends Controller
{
    /**
     * Constructor - Apply CheckNotStaff middleware to all methods.
     */
    public function __construct()
    {
        $this->middleware('check.not.staff');
    }

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
            'employee_id' => 'required|exists:users,id',
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
}
