<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SafedropResolution;
use App\Models\DailySale;
use App\Models\BankAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SafedropResolutionController extends Controller
{
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
            
            if ($pendingSafedrops > 0 || $pendingCashInHand > 0) {
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
            'resolutions.*.amount' => 'required|numeric|min:0.01',
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
            
            // Check if total resolution doesn't exceed available amount
            if (($currentlyResolved + $totalResolveAmount) > $totalAmount) {
                return response()->json([
                    'message' => 'Total resolution amount exceeds available amount',
                    'errors' => [
                        'resolutions' => ['The total amount being resolved exceeds the available amount.']
                    ]
                ], 422);
            }

            $createdResolutions = [];
            
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
                
                // Update bank account balance
                $bankAccount->increment('balance', $resolution['amount']);
                
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
            
            // Reverse the bank account balance change
            $safedropResolution->bankAccount->decrement('balance', $safedropResolution->amount);
            
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
