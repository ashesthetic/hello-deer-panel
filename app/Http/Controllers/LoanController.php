<?php

namespace App\Http\Controllers;

use App\Models\Loan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LoanController extends Controller
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
        $query = Loan::query();
        
        // Handle sorting
        $allowedSortFields = ['id', 'name', 'amount', 'currency', 'created_at', 'updated_at'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'created_at';
        }
        
        $query->orderBy($sortBy, $sortDirection);
        
        $loans = $query->paginate($perPage);
        
        return response()->json($loans);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $loan = Loan::create([
            'name' => $request->name,
            'amount' => $request->amount,
            'currency' => $request->currency ?? 'CAD',
            'notes' => $request->notes,
        ]);

        return response()->json([
            'message' => 'Loan created successfully',
            'data' => $loan
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $loan = Loan::findOrFail($id);
        
        return response()->json([
            'data' => $loan
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $loan = Loan::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'amount' => 'sometimes|required|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $loan->update($request->only(['name', 'amount', 'currency', 'notes']));

        return response()->json([
            'message' => 'Loan updated successfully',
            'data' => $loan
        ]);
    }

    /**
     * Remove the specified resource from storage (soft delete).
     */
    public function destroy(string $id)
    {
        $loan = Loan::findOrFail($id);
        $loan->delete();

        return response()->json([
            'message' => 'Loan deleted successfully'
        ]);
    }

    /**
     * Get all loans including soft deleted ones.
     */
    public function withTrashed(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        
        $query = Loan::withTrashed();
        
        $allowedSortFields = ['id', 'name', 'amount', 'currency', 'created_at', 'updated_at', 'deleted_at'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'created_at';
        }
        
        $query->orderBy($sortBy, $sortDirection);
        
        $loans = $query->paginate($perPage);
        
        return response()->json($loans);
    }

    /**
     * Restore a soft deleted loan.
     */
    public function restore(string $id)
    {
        $loan = Loan::withTrashed()->findOrFail($id);
        
        if (!$loan->trashed()) {
            return response()->json([
                'message' => 'Loan is not deleted'
            ], 400);
        }
        
        $loan->restore();

        return response()->json([
            'message' => 'Loan restored successfully',
            'data' => $loan
        ]);
    }

    /**
     * Permanently delete a loan.
     */
    public function forceDelete(string $id)
    {
        $loan = Loan::withTrashed()->findOrFail($id);
        $loan->forceDelete();

        return response()->json([
            'message' => 'Loan permanently deleted'
        ]);
    }

    /**
     * Process a loan payment (deposit or withdrawal).
     */
    public function processPayment(Request $request, string $id)
    {
        $loan = Loan::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'type' => 'required|in:deposit,withdrawal',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $amount = $request->amount;
        $type = $request->type;
        $date = $request->date;
        $notes = $request->notes ?? '';

        // Update loan amount based on payment type
        if ($type === 'deposit') {
            // Deposit reduces the loan amount
            $loan->amount = max(0, $loan->amount - $amount);
            $transactionType = 'expense';
            $description = "Loan payment (deposit) for {$loan->name}";
        } else {
            // Withdrawal increases the loan amount
            $loan->amount = $loan->amount + $amount;
            $transactionType = 'income';
            $description = "Loan withdrawal for {$loan->name}";
        }

        $loan->save();

        // Create corresponding transaction
        \App\Models\Transaction::create([
            'date' => $date,
            'amount' => $amount,
            'type' => $transactionType,
            'category' => 'Loan Payment',
            'description' => $description,
            'notes' => $notes,
            'user_id' => auth()->id(),
        ]);

        return response()->json([
            'message' => 'Payment processed successfully',
            'data' => $loan
        ]);
    }
}
