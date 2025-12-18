<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

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
            'full_legal_name' => 'required|string|max:255',
            'preferred_name' => 'nullable|string|max:255',
            'date_of_birth' => 'required|date',
            'address' => 'required|string',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'required|string|in:Canada,United States',
            'phone_number' => 'required|string|max:20',
            'alternate_number' => 'nullable|string|max:20',
            'email' => 'required|email|unique:employees,email',
            'emergency_name' => 'required|string|max:255',
            'emergency_relationship' => 'required|string|max:255',
            'emergency_address_line1' => 'nullable|string|max:255',
            'emergency_address_line2' => 'nullable|string|max:255',
            'emergency_city' => 'nullable|string|max:255',
            'emergency_state' => 'nullable|string|max:255',
            'emergency_postal_code' => 'nullable|string|max:20',
            'emergency_country' => 'nullable|string|in:Canada,United States',
            'emergency_phone' => 'required|string|max:20',
            'emergency_alternate_number' => 'nullable|string|max:20',
            'status_in_canada' => 'required|string|in:Canadian Citizen,Permanent Resident,Work Permit + Student,Work Permit,Other',
            'other_status' => 'nullable|string|max:255',
            'sin_number' => 'required|string|max:20',
            'position' => 'required|string|in:Director,Manager,Store Associate',
            'department' => 'required|string|max:255',
            'hire_date' => 'required|date',
            'hourly_rate' => 'required|numeric|min:0',
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
            'full_legal_name' => 'required|string|max:255',
            'preferred_name' => 'nullable|string|max:255',
            'date_of_birth' => 'required|date',
            'address' => 'required|string',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'required|string|in:Canada,United States',
            'phone_number' => 'required|string|max:20',
            'alternate_number' => 'nullable|string|max:20',
            'email' => ['required', 'email', Rule::unique('employees')->ignore($employee->id)],
            'emergency_name' => 'required|string|max:255',
            'emergency_relationship' => 'required|string|max:255',
            'emergency_address_line1' => 'nullable|string|max:255',
            'emergency_address_line2' => 'nullable|string|max:255',
            'emergency_city' => 'nullable|string|max:255',
            'emergency_state' => 'nullable|string|max:255',
            'emergency_postal_code' => 'nullable|string|max:20',
            'emergency_country' => 'nullable|string|in:Canada,United States',
            'emergency_phone' => 'required|string|max:20',
            'emergency_alternate_number' => 'nullable|string|max:20',
            'status_in_canada' => 'required|string|in:Canadian Citizen,Permanent Resident,Work Permit + Student,Work Permit,Other',
            'other_status' => 'nullable|string|max:255',
            'sin_number' => 'required|string|max:20',
            'position' => 'required|string|in:Director,Manager,Store Associate',
            'department' => 'required|string|max:255',
            'hire_date' => 'required|date',
            'hourly_rate' => 'required|numeric|min:0',
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
}
