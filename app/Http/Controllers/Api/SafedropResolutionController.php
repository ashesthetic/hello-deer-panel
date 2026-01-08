<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SafedropResolution;
use App\Models\DailySale;
use App\Models\BankAccount;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SafedropResolutionController extends Controller
{
    /**
     * Get the Cash account for safedrop transfers
     */
    private function getCashAccount()
    {
        // Find existing Cash account by name (case-insensitive)
        $cashAccount = BankAccount::whereRaw('LOWER(account_name) = ?', ['cash'])
            ->first();
        
        if (!$cashAccount) {
            throw new \Exception('Cash account not found. Please create a bank account with name "Cash".');
        }
        
        return $cashAccount;
    }

    /**
     * Display pending safedrops and cash in hand amounts
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Base query for daily sales with unresolved amounts
        $query = DailySale::with(['user', 'safedropResolutions'])
            ->where(function($q) {
                $q->where('safedrops_amount', '>', 0)
                  ->orWhere('cash_on_hand', '>', 0);
            });

        // Apply user restrictions for non-admin users
        if ($user->isEditor()) {
            $query->where('user_id', $user->id);
        }

        $dailySales = $query->orderBy('date', 'desc')->get();

        // Transform data to include pending amounts
        $pendingItems = [];
        
        foreach ($dailySales as $sale) {
            // Calculate resolved amounts for safedrops
            $resolvedSafedrops = $sale->safedropResolutions()
                ->where('type', 'safedrops')
                ->sum('amount');
            
            // Calculate resolved amounts for cash in hand
            $resolvedCashInHand = $sale->safedropResolutions()
                ->where('type', 'cash_in_hand')
                ->sum('amount');
            
            $pendingSafedrops = $sale->safedrops_amount - $resolvedSafedrops;
            $pendingCashInHand = $sale->cash_on_hand - $resolvedCashInHand;
            
            // Include items with any non-zero pending amounts (positive or negative)
            if ($pendingSafedrops != 0 || $pendingCashInHand != 0) {
                $pendingItems[] = [
                    'id' => $sale->id,
                    'date' => $sale->date,
                    'user' => $sale->user,
                    'safedrops' => [
                        'total_amount' => $sale->safedrops_amount,
                        'resolved_amount' => $resolvedSafedrops,
                        'pending_amount' => $pendingSafedrops
                    ],
                    'cash_in_hand' => [
                        'total_amount' => $sale->cash_on_hand,
                        'resolved_amount' => $resolvedCashInHand,
                        'pending_amount' => $pendingCashInHand
                    ]
                ];
            }
        }

        return response()->json([
            'data' => $pendingItems
        ]);
    }

    /**
     * Store new resolution(s)
     */
    public function store(Request $request)
    {
        $user = $request->user();
        
        // Only admins can resolve pending amounts
        if (!$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'daily_sale_id' => 'required|exists:daily_sales,id',
            'type' => ['required', Rule::in(['safedrops', 'cash_in_hand'])],
            'resolutions' => 'required|array|min:1',
            'resolutions.*.bank_account_id' => 'required|exists:bank_accounts,id',
            'resolutions.*.amount' => 'required|numeric|not_in:0',
            'resolutions.*.notes' => 'nullable|string|max:1000'
        ]);

        try {
            DB::beginTransaction();

            $dailySale = DailySale::findOrFail($request->daily_sale_id);
            $type = $request->type;
            
            // Calculate current resolved amount
            $currentlyResolved = SafedropResolution::where('daily_sale_id', $dailySale->id)
                ->where('type', $type)
                ->sum('amount');
            
            // Get total amount to resolve
            $totalAmount = $type === 'safedrops' ? $dailySale->safedrops_amount : $dailySale->cash_on_hand;
            
            // Calculate total amount being resolved in this request
            $totalResolveAmount = collect($request->resolutions)->sum('amount');
            
            // For negative totals, we need to check the absolute values don't exceed
            if ($totalAmount < 0) {
                // For negative amounts, the resolution amounts should also be negative
                // and their absolute value shouldn't exceed the absolute total
                if (($currentlyResolved + $totalResolveAmount) < $totalAmount) {
                    return response()->json([
                        'message' => 'Total resolution amount exceeds available amount (negative case)',
                        'errors' => [
                            'resolutions' => ['The total amount being resolved exceeds the available negative amount.']
                        ]
                    ], 422);
                }
            } else {
                // For positive amounts, check if total resolution doesn't exceed available amount
                if (($currentlyResolved + $totalResolveAmount) > $totalAmount) {
                    return response()->json([
                        'message' => 'Total resolution amount exceeds available amount',
                        'errors' => [
                            'resolutions' => ['The total amount being resolved exceeds the available amount.']
                        ]
                    ], 422);
                }
            }

            $createdResolutions = [];
            
            // Get the Cash account for transfers
            $cashAccount = $this->getCashAccount();
            
            foreach ($request->resolutions as $resolution) {
                // Verify bank account exists and user can access it
                $bankAccount = BankAccount::findOrFail($resolution['bank_account_id']);
                
                if (!$bankAccount->canBeUpdatedBy($user)) {
                    return response()->json([
                        'message' => 'Cannot access bank account: ' . $bankAccount->account_name
                    ], 403);
                }
                
                // Create the resolution record
                $safedropResolution = SafedropResolution::create([
                    'daily_sale_id' => $dailySale->id,
                    'bank_account_id' => $bankAccount->id,
                    'user_id' => $user->id,
                    'amount' => $resolution['amount'],
                    'type' => $type,
                    'notes' => $resolution['notes'] ?? null
                ]);
                
                // Check if resolving to Cash account itself
                $isCashAccount = $bankAccount->id === $cashAccount->id;
                
                if ($isCashAccount) {
                    // Resolving to Cash account - just increment Cash balance
                    // No need for transfer transaction since money is already in Cash
                    $cashAccount->increment('balance', $resolution['amount']);
                } else {
                    // Resolving to a different bank account
                    
                    // Update target bank account balance
                    $bankAccount->increment('balance', $resolution['amount']);
                    
                    // Create transaction record for safedrop resolution
                    $transactionDescription = $type === 'safedrops' 
                        ? "Safedrop resolution for {$dailySale->date}"
                        : "Cash in hand resolution for {$dailySale->date}";
                    
                    if (!empty($resolution['notes'])) {
                        $transactionDescription .= " - {$resolution['notes']}";
                    }
                    
                    if ($type === 'safedrops') {
                        // For safedrops: Create income transaction (money comes from safedrops, not Cash account)
                        $transaction = Transaction::create([
                            'type' => 'income',
                            'amount' => $resolution['amount'],
                            'description' => $transactionDescription,
                            'bank_account_id' => $bankAccount->id,
                            'transaction_date' => now()->toDateString(),
                            'reference_number' => 'SR-' . $safedropResolution->id,
                            'status' => 'completed',
                            'user_id' => $user->id,
                        ]);
                    } else {
                        // For cash in hand: Create transfer from Cash account
                        $transaction = Transaction::create([
                            'type' => 'transfer',
                            'amount' => $resolution['amount'],
                            'description' => $transactionDescription,
                            'from_bank_account_id' => $cashAccount->id,
                            'to_bank_account_id' => $bankAccount->id,
                            'transaction_date' => now()->toDateString(),
                            'reference_number' => 'SR-' . $safedropResolution->id,
                            'status' => 'completed',
                            'user_id' => $user->id,
                        ]);
                        
                        // Only deduct from cash account for cash in hand resolutions
                        $cashAccount->decrement('balance', $resolution['amount']);
                    }
                }
                
                $createdResolutions[] = $safedropResolution->load(['bankAccount', 'user']);
            }

            DB::commit();

            return response()->json([
                'message' => 'Resolution completed successfully',
                'data' => $createdResolutions
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'An error occurred while processing the resolution',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display resolution history
     */
    public function history(Request $request)
    {
        $user = $request->user();
        
        $query = SafedropResolution::with(['dailySale.user', 'bankAccount', 'user'])
            ->orderBy('created_at', 'desc');

        // Apply user restrictions for non-admin users
        if (!$user->isAdmin()) {
            $query->where('user_id', $user->id);
        }

        $resolutions = $query->paginate(20);

        return response()->json($resolutions);
    }

    /**
     * Delete a resolution (admin only)
     */
    public function destroy(Request $request, SafedropResolution $safedropResolution)
    {
        $user = $request->user();
        
        if (!$safedropResolution->canBeDeletedBy($user)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        try {
            DB::beginTransaction();
            
            // Get the cash account
            $cashAccount = $this->getCashAccount();
            
            // Check if resolution was to Cash account itself
            $isCashAccount = $safedropResolution->bank_account_id === $cashAccount->id;
            
            if ($isCashAccount) {
                // Resolution was to Cash account - just decrement Cash balance
                $cashAccount->decrement('balance', $safedropResolution->amount);
            } else {
                // Resolution was to a different bank account - reverse the transfer
                
                // Find and delete the associated transaction
                $transaction = Transaction::where('reference_number', 'SR-' . $safedropResolution->id)
                    ->where('type', 'transfer')
                    ->first();
                
                if ($transaction) {
                    // Reverse the cash account balance (increase it back)
                    $cashAccount->increment('balance', $safedropResolution->amount);
                    
                    // Delete the transaction
                    $transaction->delete();
                }
                
                // Reverse the bank account balance change
                $safedropResolution->bankAccount->decrement('balance', $safedropResolution->amount);
            }
            
            // Delete the resolution
            $safedropResolution->delete();
            
            DB::commit();

            return response()->json([
                'message' => 'Resolution deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'message' => 'An error occurred while deleting the resolution',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
