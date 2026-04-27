<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DepartmentSale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DepartmentSaleController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 50);
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');

        $allowedSortFields = ['id', 'department_number', 'qty', 'price', 'date', 'created_at', 'updated_at'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'created_at';
        }

        $query = DepartmentSale::with('department');

        if ($request->filled('department_number')) {
            $query->where('department_number', $request->department_number);
        }

        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }

        $query->orderBy($sortBy, $sortDirection);

        return response()->json($query->paginate($perPage));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'department_number' => 'required|integer|exists:departments,department_number',
            'qty' => 'required|numeric|min:0',
            'price' => 'required|numeric|min:0',
            'date' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $sale = DepartmentSale::create($request->only(['department_number', 'qty', 'price', 'date']));
        $sale->load('department');

        return response()->json([
            'message' => 'Department sale created successfully',
            'data' => $sale
        ], 201);
    }

    public function show(string $id)
    {
        $sale = DepartmentSale::with('department')->findOrFail($id);

        return response()->json([
            'data' => $sale
        ]);
    }

    public function update(Request $request, string $id)
    {
        $sale = DepartmentSale::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'department_number' => 'sometimes|required|integer|exists:departments,department_number',
            'qty' => 'sometimes|required|numeric|min:0',
            'price' => 'sometimes|required|numeric|min:0',
            'date' => 'sometimes|nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $sale->update($request->only(['department_number', 'qty', 'price', 'date']));
        $sale->load('department');

        return response()->json([
            'message' => 'Department sale updated successfully',
            'data' => $sale
        ]);
    }

    public function destroy(string $id)
    {
        $sale = DepartmentSale::findOrFail($id);
        $sale->delete();

        return response()->json([
            'message' => 'Department sale deleted successfully'
        ]);
    }

    public function restore(string $id)
    {
        $sale = DepartmentSale::withTrashed()->findOrFail($id);

        if (!$sale->trashed()) {
            return response()->json(['message' => 'Department sale is not deleted'], 400);
        }

        $sale->restore();
        $sale->load('department');

        return response()->json([
            'message' => 'Department sale restored successfully',
            'data' => $sale
        ]);
    }

    public function forceDelete(string $id)
    {
        $sale = DepartmentSale::withTrashed()->findOrFail($id);
        $sale->forceDelete();

        return response()->json([
            'message' => 'Department sale permanently deleted'
        ]);
    }
}
