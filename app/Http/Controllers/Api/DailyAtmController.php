<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DailyAtm;
use App\Models\BankAccount;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DailyAtmController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Staff users have no access
        if ($user->isStaff()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // Only admins can view
        if (!$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $perPage = $request->input('per_page', 10);
        $sortBy = $request->input('sort_by', 'date');
        $sortDirection = $request->input('sort_direction', 'desc');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        $query = DailyAtm::query();
        
        // Add date range filter if provided
        if ($startDate) {
            $query->where('date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('date', '<=', $endDate);
        }
        
        // Validate and apply sorting
        $allowedSortFields = ['date', 'no_of_transactions', 'withdraw'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'date';
        }
        $query->orderBy($sortBy, $sortDirection);
        
        $dailyAtm = $query->paginate($perPage);
        
        return response()->json($dailyAtm);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        
        // Staff users have no access
        if ($user->isStaff()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // Only admins can create
        if (!$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $request->validate([
            'date' => 'required|date|unique:daily_atm,date',
            'no_of_transactions' => 'required|integer|min:0',
            'withdraw' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Calculate fee based on number of transactions and surcharge rate
        $surchargeRate = config('profit.atm_surcharge_rate', 2.5);
        $fee = $request->no_of_transactions * $surchargeRate;

        // Use database transaction for atomicity
        DB::beginTransaction();
        
        try {
            // Create the daily ATM record
            $dailyAtm = DailyAtm::create([
                'date' => $request->date,
                'no_of_transactions' => $request->no_of_transactions,
                'withdraw' => $request->withdraw,
                'fee' => $fee,
                'notes' => $request->notes,
            ]);

            // Find the ATM bank account by name
            $atmAccount = BankAccount::where('account_name', 'ATM')
                ->where('is_active', true)
                ->first();

            if (!$atmAccount) {
                throw new \Exception('ATM bank account not found. Please create an active bank account with name "ATM".');
            }

            // Format the date for the transaction description
            $formattedDate = Carbon::parse($request->date)->format('F d, Y');

            // Create the expense transaction
            $transaction = Transaction::create([
                'type' => 'expense',
                'amount' => $request->withdraw,
                'description' => "Total ATM Withdrawal - {$formattedDate}",
                'notes' => "Auto-generated from daily ATM entry",
                'bank_account_id' => $atmAccount->id,
                'transaction_date' => $request->date,
                'reference_number' => 'ATM-' . Carbon::parse($request->date)->format('Ymd'),
                'status' => 'completed',
                'user_id' => $user->id,
            ]);

            // Deduct the amount from the ATM bank account
            $atmAccount->decrement('balance', $request->withdraw);

            DB::commit();

            return response()->json([
                'message' => 'Daily ATM record created successfully',
                'data' => $dailyAtm,
                'transaction' => $transaction,
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create ATM record: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, DailyAtm $dailyAtm)
    {
        $user = $request->user();
        
        // Staff users have no access
        if ($user->isStaff()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // Only admins can view
        if (!$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        return response()->json($dailyAtm);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DailyAtm $dailyAtm)
    {
        $user = $request->user();
        
        // Staff users have no access
        if ($user->isStaff()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // Only admins can update
        if (!$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        $request->validate([
            'date' => 'required|date|unique:daily_atm,date,' . $dailyAtm->id,
            'no_of_transactions' => 'required|integer|min:0',
            'withdraw' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Calculate fee based on number of transactions and surcharge rate
        $surchargeRate = config('profit.atm_surcharge_rate', 2.5);
        $fee = $request->no_of_transactions * $surchargeRate;

        DB::beginTransaction();
        
        try {
            $oldWithdraw = $dailyAtm->withdraw;
            $oldDate = $dailyAtm->date;
            $newWithdraw = $request->withdraw;
            $newDate = $request->date;

            // Find the ATM bank account
            $atmAccount = BankAccount::where('account_name', 'ATM')
                ->where('is_active', true)
                ->first();

            if (!$atmAccount) {
                throw new \Exception('ATM bank account not found.');
            }

            // Find the existing transaction for this ATM entry
            $oldReferenceNumber = 'ATM-' . Carbon::parse($oldDate)->format('Ymd');
            $transaction = Transaction::where('reference_number', $oldReferenceNumber)
                ->where('type', 'expense')
                ->where('bank_account_id', $atmAccount->id)
                ->first();

            if ($transaction) {
                // Reverse the old transaction effect on balance
                $atmAccount->increment('balance', $oldWithdraw);

                // Update the transaction with new values
                $formattedDate = Carbon::parse($newDate)->format('F d, Y');
                $transaction->update([
                    'amount' => $newWithdraw,
                    'description' => "Total ATM Withdrawal - {$formattedDate}",
                    'transaction_date' => $newDate,
                    'reference_number' => 'ATM-' . Carbon::parse($newDate)->format('Ymd'),
                ]);

                // Apply the new transaction effect on balance
                $atmAccount->decrement('balance', $newWithdraw);
            }

            // Update the ATM record
            $dailyAtm->update([
                'date' => $newDate,
                'no_of_transactions' => $request->no_of_transactions,
                'withdraw' => $newWithdraw,
                'fee' => $fee,
                'notes' => $request->notes,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Daily ATM record updated successfully',
                'data' => $dailyAtm
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update ATM record: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage (soft delete).
     */
    public function destroy(Request $request, DailyAtm $dailyAtm)
    {
        $user = $request->user();
        
        // Staff users have no access
        if ($user->isStaff()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // Only admins can delete
        if (!$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        DB::beginTransaction();
        
        try {
            // Find the ATM bank account
            $atmAccount = BankAccount::where('account_name', 'ATM')
                ->where('is_active', true)
                ->first();

            if (!$atmAccount) {
                throw new \Exception('ATM bank account not found.');
            }

            // Find and delete the associated transaction
            $referenceNumber = 'ATM-' . Carbon::parse($dailyAtm->date)->format('Ymd');
            $transaction = Transaction::where('reference_number', $referenceNumber)
                ->where('type', 'expense')
                ->where('bank_account_id', $atmAccount->id)
                ->first();

            if ($transaction) {
                // Reverse the transaction effect on balance (add back the withdrawn amount)
                $atmAccount->increment('balance', $dailyAtm->withdraw);
                
                // Delete the transaction
                $transaction->delete();
            }

            // Soft delete the ATM record
            $dailyAtm->delete();

            DB::commit();

            return response()->json([
                'message' => 'Daily ATM record deleted successfully'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to delete ATM record: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Resolve an ATM entry (mark as resolved and update bank account)
     */
    public function resolve(Request $request, DailyAtm $dailyAtm)
    {
        $user = $request->user();
        
        // Staff users have no access
        if ($user->isStaff()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // Only admins can resolve
        if (!$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check if already resolved
        if ($dailyAtm->resolved) {
            return response()->json(['message' => 'ATM record is already resolved'], 400);
        }

        $request->validate([
            'bank_account_id' => 'required|exists:bank_accounts,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        DB::beginTransaction();
        
        try {
            // Get the bank account where money will be deposited
            $bankAccount = BankAccount::findOrFail($request->bank_account_id);

            if (!$bankAccount->is_active) {
                throw new \Exception('Bank account is not active');
            }

            // Format the date for the transaction description
            $formattedDate = Carbon::parse($dailyAtm->date)->format('F d, Y');

            // Create the income transaction for the resolution
            $transaction = Transaction::create([
                'type' => 'income',
                'amount' => $dailyAtm->withdraw,
                'description' => "ATM Withdrawal Resolution - {$formattedDate}",
                'notes' => $request->notes ?? "Resolved ATM withdrawal from {$formattedDate}",
                'bank_account_id' => $bankAccount->id,
                'transaction_date' => now()->toDateString(),
                'reference_number' => 'ATM-RES-' . Carbon::parse($dailyAtm->date)->format('Ymd'),
                'status' => 'completed',
                'user_id' => $user->id,
            ]);

            // Add the amount to the bank account (since this is money coming in)
            $bankAccount->increment('balance', $dailyAtm->withdraw);

            // Mark the ATM entry as resolved
            $dailyAtm->update(['resolved' => true]);

            DB::commit();

            return response()->json([
                'message' => 'ATM withdrawal resolved successfully',
                'data' => $dailyAtm,
                'transaction' => $transaction,
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to resolve ATM record: ' . $e->getMessage()
            ], 500);
        }
    }
}
