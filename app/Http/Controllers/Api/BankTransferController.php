<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\BankAccount;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BankTransferController extends Controller
{
    /**
     * Get available bank accounts for transfers
     */
    public function getBankAccounts(): JsonResponse
    {
        $user = Auth::user();
        
        // Get bank accounts the user has access to
        $bankAccounts = BankAccount::whereHas('users', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
        ->orWhere('owner_id', $user->id)
        ->select(['id', 'name', 'account_number', 'balance'])
        ->get();

        return response()->json([
            'success' => true,
            'data' => $bankAccounts
        ]);
    }

    /**
     * Create a bank-to-bank transfer
     */
    public function transfer(Request $request): JsonResponse
    {
        $user = Auth::user();

        // Validate request
        $validated = $request->validate([
            'from_bank_account_id' => 'required|exists:bank_accounts,id',
            'to_bank_account_id' => 'required|exists:bank_accounts,id|different:from_bank_account_id',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
            'reference_number' => 'nullable|string|max:100',
        ]);

        // Get bank accounts
        $fromBankAccount = BankAccount::find($validated['from_bank_account_id']);
        $toBankAccount = BankAccount::find($validated['to_bank_account_id']);

        // Check ACL for bank account access
        if (!$user->canAccessBankAccount($fromBankAccount)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to access the source bank account.'
            ], 403);
        }

        if (!$user->canAccessBankAccount($toBankAccount)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to access the destination bank account.'
            ], 403);
        }

        // Check if source account has sufficient balance
        if ($fromBankAccount->balance < $validated['amount']) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient balance in the source account.'
            ], 422);
        }

        try {
            // Create transfer transactions using the static method
            $transactions = Transaction::createTransfer(
                $fromBankAccount,
                $toBankAccount,
                $validated['amount'],
                $validated['description'],
                $user,
                $validated['reference_number'] ?? null
            );

            return response()->json([
                'success' => true,
                'message' => 'Bank transfer completed successfully.',
                'data' => $transactions
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error processing transfer: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get transfer history
     */
    public function history(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        // Base query for transfer transactions with ACL
        $query = Transaction::with(['user', 'bankAccount', 'transferTransaction'])
            ->where('type', 'transfer')
            ->whereHas('user', function ($query) use ($user) {
                return $user->canViewTransactions($query);
            });

        // Apply filters
        if ($request->has('bank_account_id') && $request->bank_account_id !== 'all') {
            $query->where(function ($q) use ($request) {
                $q->where('bank_account_id', $request->bank_account_id)
                  ->orWhere('to_bank_account_id', $request->bank_account_id);
            });
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
        $transfers = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $transfers
        ]);
    }

    /**
     * Get transfer details by ID
     */
    public function show(string $id): JsonResponse
    {
        $user = Auth::user();
        
        $transfer = Transaction::with(['user', 'bankAccount', 'transferTransaction'])
            ->where('type', 'transfer')
            ->find($id);

        if (!$transfer) {
            return response()->json([
                'success' => false,
                'message' => 'Transfer not found.'
            ], 404);
        }

        // Check ACL
        if (!$transfer->canBeViewedBy($user)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to view this transfer.'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $transfer
        ]);
    }

    /**
     * Get transfer summary statistics
     */
    public function summary(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        // Base query with ACL
        $query = Transaction::where('type', 'transfer')
            ->whereHas('user', function ($query) use ($user) {
                return $user->canViewTransactions($query);
            });

        // Apply date filters if provided
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Get summary data
        $summary = [
            'total_transfers' => $query->count(),
            'total_amount' => $query->sum('amount'),
            'avg_amount' => $query->avg('amount'),
            'largest_transfer' => $query->max('amount'),
            'smallest_transfer' => $query->min('amount'),
        ];

        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }

    /**
     * Cancel a pending transfer (if applicable)
     */
    public function cancel(string $id): JsonResponse
    {
        $user = Auth::user();
        
        $transfer = Transaction::where('type', 'transfer')->find($id);

        if (!$transfer) {
            return response()->json([
                'success' => false,
                'message' => 'Transfer not found.'
            ], 404);
        }

        // Check ACL
        if (!$transfer->canBeDeletedBy($user)) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have permission to cancel this transfer.'
            ], 403);
        }

        try {
            DB::transaction(function () use ($transfer) {
                // Find the corresponding transfer transaction
                $correspondingTransfer = Transaction::where('transfer_transaction_id', $transfer->id)
                    ->orWhere('id', $transfer->transfer_transaction_id)
                    ->first();

                if ($correspondingTransfer) {
                    // Reverse the balance changes
                    $fromAccount = BankAccount::find($transfer->bank_account_id);
                    $toAccount = BankAccount::find($transfer->to_bank_account_id);

                    if ($fromAccount && $toAccount) {
                        $fromAccount->increment('balance', $transfer->amount);
                        $toAccount->decrement('balance', $transfer->amount);
                    }

                    // Delete both transactions
                    $correspondingTransfer->delete();
                }
                
                $transfer->delete();
            });

            return response()->json([
                'success' => true,
                'message' => 'Transfer cancelled successfully.'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error cancelling transfer: ' . $e->getMessage()
            ], 500);
        }
    }
}
