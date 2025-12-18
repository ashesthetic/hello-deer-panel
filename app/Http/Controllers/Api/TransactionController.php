<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\BankAccount;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class TransactionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        // Start with base query filtered by ACL
        $query = Transaction::with(['user', 'bankAccount', 'vendorInvoice'])
            ->whereHas('user', function ($query) use ($user) {
                return $user->canViewTransactions($query);
            });

        // Apply filters
        if ($request->has('type') && $request->type !== 'all') {
            $query->where('type', $request->type);
        }

        if ($request->has('bank_account_id') && $request->bank_account_id !== 'all') {
            $query->where('bank_account_id', $request->bank_account_id);
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->has('amount_min')) {
            $query->where('amount', '>=', $request->amount_min);
        }

        if ($request->has('amount_max')) {
            $query->where('amount', '<=', $request->amount_max);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%");
            });
        }

        // Apply sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Paginate results
        $perPage = $request->get('per_page', 15);
        $transactions = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $transactions
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $user = Auth::user();

        // Validate request
        $validated = $request->validate([
            'type' => ['required', Rule::in(['income', 'expense', 'transfer'])],
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'to_bank_account_id' => 'nullable|exists:bank_accounts,id|different:bank_account_id',
            'reference_number' => 'nullable|string|max:100',
            'vendor_invoice_id' => 'nullable|exists:vendor_invoices,id',
        ]);

        // Additional validation for transfer type
        if ($validated['type'] === 'transfer' && empty($validated['to_bank_account_id'])) {
            return response()->json([
                'success' => false,
                'message' => 'To bank account is required for transfer transactions.'
            ], 422);
        }

        // Check ACL for bank account access
        $bankAccount = BankAccount::find($validated['bank_account_id']);
        if (!$user->canAccessBankAccount($bankAccount)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to access this bank account.'
            ], 403);
        }

        if (isset($validated['to_bank_account_id'])) {
            $toBankAccount = BankAccount::find($validated['to_bank_account_id']);
            if (!$user->canAccessBankAccount($toBankAccount)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You do not have permission to access the destination bank account.'
                ], 403);
            }
        }

        try {
            if ($validated['type'] === 'transfer') {
                // Create transfer transactions
                $transactions = Transaction::createTransfer(
                    $bankAccount,
                    $toBankAccount,
                    $validated['amount'],
                    $validated['description'],
                    $user,
                    $validated['reference_number'] ?? null
                );

                return response()->json([
                    'success' => true,
                    'message' => 'Transfer transactions created successfully.',
                    'data' => $transactions
                ], 201);
            } else {
                // Create single transaction
                $transaction = Transaction::create([
                    'type' => $validated['type'],
                    'amount' => $validated['amount'],
                    'description' => $validated['description'],
                    'bank_account_id' => $validated['bank_account_id'],
                    'user_id' => $user->id,
                    'reference_number' => $validated['reference_number'] ?? null,
                    'vendor_invoice_id' => $validated['vendor_invoice_id'] ?? null,
                ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Transaction created successfully.',
                    'data' => $transaction->load(['user', 'bankAccount', 'vendorInvoice'])
                ], 201);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating transaction: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        $user = Auth::user();
        
        $transaction = Transaction::with(['user', 'bankAccount', 'vendorInvoice', 'transferTransaction'])
            ->find($id);

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found.'
            ], 404);
        }

        // Check ACL
        if (!$transaction->canBeViewedBy($user)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view this transaction.'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $transaction
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $user = Auth::user();
        
        $transaction = Transaction::find($id);

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found.'
            ], 404);
        }

        // Check ACL
        if (!$transaction->canBeUpdatedBy($user)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to update this transaction.'
            ], 403);
        }

        // Validate request
        $validated = $request->validate([
            'description' => 'sometimes|required|string|max:255',
            'reference_number' => 'nullable|string|max:100',
        ]);

        try {
            $transaction->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Transaction updated successfully.',
                'data' => $transaction->load(['user', 'bankAccount', 'vendorInvoice'])
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating transaction: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id): JsonResponse
    {
        $user = Auth::user();
        
        $transaction = Transaction::find($id);

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found.'
            ], 404);
        }

        // Check ACL
        if (!$transaction->canBeDeletedBy($user)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to delete this transaction.'
            ], 403);
        }

        try {
            $transaction->delete();

            return response()->json([
                'success' => true,
                'message' => 'Transaction deleted successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting transaction: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get summary statistics for transactions
     */
    public function summary(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        // Base query with ACL
        $query = Transaction::whereHas('user', function ($query) use ($user) {
            return $user->canViewTransactions($query);
        });

        // Apply date filters if provided
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Get totals by type
        $summary = [
            'total_income' => (clone $query)->where('type', 'income')->sum('amount'),
            'total_expenses' => (clone $query)->where('type', 'expense')->sum('amount'),
            'total_transfers' => (clone $query)->where('type', 'transfer')->sum('amount'),
            'net_amount' => 0,
            'transaction_count' => $query->count(),
        ];

        $summary['net_amount'] = $summary['total_income'] - $summary['total_expenses'];

        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }
}
