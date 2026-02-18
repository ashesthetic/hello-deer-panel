<?php

namespace App\Http\Controllers;

use App\Models\ExpenseType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ExpenseTypeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        
        // Build query
        $query = ExpenseType::with(['parentExpenseType', 'childExpenseTypes']);
        
        // Handle sorting
        $allowedSortFields = ['id', 'expense_type', 'parent_expense_type_id', 'created_at', 'updated_at'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'created_at';
        }
        
        $query->orderBy($sortBy, $sortDirection);
        
        $expenseTypes = $query->paginate($perPage);
        
        return response()->json($expenseTypes);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'expense_type' => 'required|string|max:255',
            'parent_expense_type_id' => 'nullable|exists:expense_types,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $expenseType = ExpenseType::create([
            'expense_type' => $request->expense_type,
            'parent_expense_type_id' => $request->parent_expense_type_id,
        ]);

        $expenseType->load(['parentExpenseType', 'childExpenseTypes']);

        return response()->json([
            'message' => 'Expense type created successfully',
            'data' => $expenseType
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $expenseType = ExpenseType::with(['parentExpenseType', 'childExpenseTypes', 'expenseBreakdowns'])
            ->findOrFail($id);
        
        return response()->json([
            'data' => $expenseType
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $expenseType = ExpenseType::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'expense_type' => 'sometimes|required|string|max:255',
            'parent_expense_type_id' => 'nullable|exists:expense_types,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Prevent circular reference
        if ($request->has('parent_expense_type_id') && $request->parent_expense_type_id == $id) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => ['parent_expense_type_id' => ['An expense type cannot be its own parent']]
            ], 422);
        }

        $expenseType->update($request->only(['expense_type', 'parent_expense_type_id']));
        $expenseType->load(['parentExpenseType', 'childExpenseTypes']);

        return response()->json([
            'message' => 'Expense type updated successfully',
            'data' => $expenseType
        ]);
    }

    /**
     * Remove the specified resource from storage (soft delete).
     */
    public function destroy(string $id)
    {
        $expenseType = ExpenseType::findOrFail($id);
        
        // Check if there are any child expense types
        if ($expenseType->childExpenseTypes()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete expense type with child expense types',
                'errors' => ['expense_type' => ['Please delete or reassign child expense types first']]
            ], 422);
        }
        
        $expenseType->delete();

        return response()->json([
            'message' => 'Expense type deleted successfully'
        ]);
    }

    /**
     * Get all expense types including soft deleted ones.
     */
    public function withTrashed(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        
        $query = ExpenseType::withTrashed()->with(['parentExpenseType', 'childExpenseTypes']);
        
        $allowedSortFields = ['id', 'expense_type', 'parent_expense_type_id', 'created_at', 'updated_at', 'deleted_at'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'created_at';
        }
        
        $query->orderBy($sortBy, $sortDirection);
        
        $expenseTypes = $query->paginate($perPage);
        
        return response()->json($expenseTypes);
    }

    /**
     * Restore a soft deleted expense type.
     */
    public function restore(string $id)
    {
        $expenseType = ExpenseType::withTrashed()->findOrFail($id);
        
        if (!$expenseType->trashed()) {
            return response()->json([
                'message' => 'Expense type is not deleted'
            ], 400);
        }
        
        $expenseType->restore();
        $expenseType->load(['parentExpenseType', 'childExpenseTypes']);

        return response()->json([
            'message' => 'Expense type restored successfully',
            'data' => $expenseType
        ]);
    }

    /**
     * Permanently delete an expense type.
     */
    public function forceDelete(string $id)
    {
        $expenseType = ExpenseType::withTrashed()->findOrFail($id);
        
        // Check if there are any child expense types
        if ($expenseType->childExpenseTypes()->withTrashed()->count() > 0) {
            return response()->json([
                'message' => 'Cannot permanently delete expense type with child expense types',
                'errors' => ['expense_type' => ['Please permanently delete child expense types first']]
            ], 422);
        }
        
        $expenseType->forceDelete();

        return response()->json([
            'message' => 'Expense type permanently deleted'
        ]);
    }

    /**
     * Get all root expense types (those without parents).
     */
    public function roots(Request $request)
    {
        $expenseTypes = ExpenseType::whereNull('parent_expense_type_id')
            ->with(['childExpenseTypes'])
            ->orderBy('expense_type')
            ->get();
        
        return response()->json([
            'data' => $expenseTypes
        ]);
    }
}
