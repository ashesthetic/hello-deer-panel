<?php

namespace App\Http\Controllers;

use App\Models\ExpenseBreakdown;
use App\Models\ExpenseType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ExpenseBreakdownController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $sortBy = $request->input('sort_by', 'date');
        $sortDirection = $request->input('sort_direction', 'desc');
        
        // Build query
        $query = ExpenseBreakdown::with(['expenseType']);
        
        // Filter by expense type(s)
        if ($request->has('expense_type_id')) {
            $query->where('expense_type_id', $request->expense_type_id);
        } elseif ($request->has('expense_type_ids')) {
            $expenseTypeIds = is_array($request->expense_type_ids) 
                ? $request->expense_type_ids 
                : explode(',', $request->expense_type_ids);
            $query->whereIn('expense_type_id', $expenseTypeIds);
        }
        
        // Filter by date range
        if ($request->has('start_date')) {
            $query->whereDate('date', '>=', $request->start_date);
        }
        
        if ($request->has('end_date')) {
            $query->whereDate('date', '<=', $request->end_date);
        }
        
        // Handle sorting
        $allowedSortFields = ['id', 'date', 'expense_type_id', 'amount', 'created_at', 'updated_at'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'date';
        }
        
        $query->orderBy($sortBy, $sortDirection);
        
        $expenseBreakdowns = $query->paginate($perPage);
        
        return response()->json($expenseBreakdowns);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'expense_type_id' => 'required|exists:expense_types,id',
            'amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $expenseBreakdown = ExpenseBreakdown::create([
            'date' => $request->date,
            'expense_type_id' => $request->expense_type_id,
            'amount' => $request->amount,
            'notes' => $request->notes,
        ]);

        $expenseBreakdown->load('expenseType');

        return response()->json([
            'message' => 'Expense breakdown created successfully',
            'data' => $expenseBreakdown
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $expenseBreakdown = ExpenseBreakdown::with('expenseType')->findOrFail($id);
        
        return response()->json([
            'data' => $expenseBreakdown
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $expenseBreakdown = ExpenseBreakdown::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'date' => 'sometimes|required|date',
            'expense_type_id' => 'sometimes|required|exists:expense_types,id',
            'amount' => 'sometimes|required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $expenseBreakdown->update($request->only(['date', 'expense_type_id', 'amount', 'notes']));
        $expenseBreakdown->load('expenseType');

        return response()->json([
            'message' => 'Expense breakdown updated successfully',
            'data' => $expenseBreakdown
        ]);
    }

    /**
     * Remove the specified resource from storage (soft delete).
     */
    public function destroy(string $id)
    {
        $expenseBreakdown = ExpenseBreakdown::findOrFail($id);
        $expenseBreakdown->delete();

        return response()->json([
            'message' => 'Expense breakdown deleted successfully'
        ]);
    }

    /**
     * Get all expense breakdowns including soft deleted ones.
     */
    public function withTrashed(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $sortBy = $request->input('sort_by', 'date');
        $sortDirection = $request->input('sort_direction', 'desc');
        
        $query = ExpenseBreakdown::withTrashed()->with('expenseType');
        
        $allowedSortFields = ['id', 'date', 'expense_type_id', 'amount', 'created_at', 'updated_at', 'deleted_at'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'date';
        }
        
        $query->orderBy($sortBy, $sortDirection);
        
        $expenseBreakdowns = $query->paginate($perPage);
        
        return response()->json($expenseBreakdowns);
    }

    /**
     * Restore a soft deleted expense breakdown.
     */
    public function restore(string $id)
    {
        $expenseBreakdown = ExpenseBreakdown::withTrashed()->findOrFail($id);
        
        if (!$expenseBreakdown->trashed()) {
            return response()->json([
                'message' => 'Expense breakdown is not deleted'
            ], 400);
        }
        
        $expenseBreakdown->restore();
        $expenseBreakdown->load('expenseType');

        return response()->json([
            'message' => 'Expense breakdown restored successfully',
            'data' => $expenseBreakdown
        ]);
    }

    /**
     * Permanently delete an expense breakdown.
     */
    public function forceDelete(string $id)
    {
        $expenseBreakdown = ExpenseBreakdown::withTrashed()->findOrFail($id);
        $expenseBreakdown->forceDelete();

        return response()->json([
            'message' => 'Expense breakdown permanently deleted'
        ]);
    }

    /**
     * Get summary statistics for expense breakdowns.
     */
    public function summary(Request $request)
    {
        $query = ExpenseBreakdown::query();
        
        // Filter by date range
        if ($request->has('start_date')) {
            $query->whereDate('date', '>=', $request->start_date);
        }
        
        if ($request->has('end_date')) {
            $query->whereDate('date', '<=', $request->end_date);
        }
        
        // Get total by expense type
        $byExpenseType = $query->with('expenseType')
            ->selectRaw('expense_type_id, SUM(amount) as total_amount, COUNT(*) as count')
            ->groupBy('expense_type_id')
            ->get();
        
        // Get overall total
        $overallTotal = $query->sum('amount');
        
        return response()->json([
            'data' => [
                'by_expense_type' => $byExpenseType,
                'overall_total' => $overallTotal,
            ]
        ]);
    }

    /**
     * Get available expense types for dropdown.
     */
    public function getExpenseTypes()
    {
        // Load expense types with nested children (recursive)
        $expenseTypes = ExpenseType::with(['childExpenseTypes.childExpenseTypes.childExpenseTypes'])
            ->orderBy('expense_type')
            ->get();
        
        return response()->json([
            'data' => $expenseTypes
        ]);
    }
}
