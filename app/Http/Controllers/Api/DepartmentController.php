<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Department;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 15);
        $sortBy = $request->input('sort_by', 'department_number');
        $sortDirection = $request->input('sort_direction', 'asc');

        $allowedSortFields = ['department_number', 'name', 'created_at', 'updated_at'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'department_number';
        }

        $query = Department::query();

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        $query->orderBy($sortBy, $sortDirection);

        return response()->json($query->paginate($perPage));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'department_number' => 'required|integer|min:1|unique:departments,department_number',
            'name' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $department = Department::create($request->only(['department_number', 'name']));

        return response()->json([
            'message' => 'Department created successfully',
            'data' => $department
        ], 201);
    }

    public function show(string $departmentNumber)
    {
        $department = Department::with('products')->findOrFail($departmentNumber);

        return response()->json([
            'data' => $department
        ]);
    }

    public function update(Request $request, string $departmentNumber)
    {
        $department = Department::findOrFail($departmentNumber);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $department->update($request->only(['name']));

        return response()->json([
            'message' => 'Department updated successfully',
            'data' => $department
        ]);
    }

    public function destroy(string $departmentNumber)
    {
        $department = Department::findOrFail($departmentNumber);

        if ($department->products()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete department with existing products',
                'errors' => ['department' => ['Please remove or reassign products first']]
            ], 422);
        }

        $department->delete();

        return response()->json([
            'message' => 'Department deleted successfully'
        ]);
    }

    public function restore(string $departmentNumber)
    {
        $department = Department::withTrashed()->findOrFail($departmentNumber);

        if (!$department->trashed()) {
            return response()->json(['message' => 'Department is not deleted'], 400);
        }

        $department->restore();

        return response()->json([
            'message' => 'Department restored successfully',
            'data' => $department
        ]);
    }

    public function forceDelete(string $departmentNumber)
    {
        $department = Department::withTrashed()->findOrFail($departmentNumber);

        if ($department->products()->withTrashed()->count() > 0) {
            return response()->json([
                'message' => 'Cannot permanently delete department with existing products',
                'errors' => ['department' => ['Please permanently delete products first']]
            ], 422);
        }

        $department->forceDelete();

        return response()->json([
            'message' => 'Department permanently deleted'
        ]);
    }
}
