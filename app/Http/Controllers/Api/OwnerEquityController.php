<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Owner;
use App\Models\OwnerEquity;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Utils\TimezoneUtil;

class OwnerEquityController extends Controller
{
    /**
     * Display a listing of owner equity transactions
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = $request->input('per_page', 10);
        $sortBy = $request->input('sort_by', 'transaction_date');
        $sortDirection = $request->input('sort_direction', 'desc');
        $search = $request->input('search');
        $ownerId = $request->input('owner_id');
        $transactionType = $request->input('transaction_type');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        // Build query
        $query = OwnerEquity::with(['owner', 'user']);
        
        // Filter by owner if specified
        if ($ownerId) {
            $query->where('owner_id', $ownerId);
        }
        
        // Filter by transaction type if specified
        if ($transactionType) {
            $query->where('transaction_type', $transactionType);
        }
        
        // Filter by date range if specified
        if ($startDate && $endDate) {
            $query->whereBetween('transaction_date', [$startDate, $endDate]);
        }
        
        // Add search filter if provided
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%")
                  ->orWhereHas('owner', function($ownerQuery) use ($search) {
                      $ownerQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }
        
        // Handle sorting
        $allowedSortFields = ['transaction_date', 'amount', 'transaction_type', 'created_at', 'updated_at'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'transaction_date';
        }
        $query->orderBy($sortBy, $sortDirection);
        
        $transactions = $query->paginate($perPage);
        
        return response()->json($transactions);
    }

    /**
     * Store a newly created equity transaction
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->canCreate()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'owner_id' => 'required|exists:owners,id',
            'transaction_type' => 'required|in:contribution,withdrawal,distribution,adjustment',
            'amount' => 'required|numeric|min:0.01',
            'transaction_date' => 'required|date',
            'reference_number' => 'nullable|string|max:255',
            'payment_method' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        // Verify the owner exists and user has access
        $owner = Owner::findOrFail($request->owner_id);
        if ($user->isEditor() && $owner->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $transaction = OwnerEquity::create([
            'owner_id' => $request->owner_id,
            'transaction_type' => $request->transaction_type,
            'amount' => $request->amount,
            'transaction_date' => $request->transaction_date,
            'reference_number' => $request->reference_number,
            'payment_method' => $request->payment_method,
            'description' => $request->description,
            'notes' => $request->notes,
            'user_id' => $user->id,
        ]);

        $transaction->load(['owner', 'user']);

        return response()->json($transaction, 201);
    }

    /**
     * Display the specified equity transaction
     */
    public function show(Request $request, OwnerEquity $ownerEquity): JsonResponse
    {
        $user = $request->user();
        
        // Check if user can view this transaction
        if ($user->isEditor() && $ownerEquity->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $ownerEquity->load(['owner', 'user']);

        return response()->json($ownerEquity);
    }

    /**
     * Update the specified equity transaction
     */
    public function update(Request $request, OwnerEquity $ownerEquity): JsonResponse
    {
        $user = $request->user();
        
        // Check if user can update this transaction
        if (!$user->canUpdate()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        if ($user->isEditor() && $ownerEquity->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'owner_id' => 'required|exists:owners,id',
            'transaction_type' => 'required|in:contribution,withdrawal,distribution,adjustment',
            'amount' => 'required|numeric|min:0.01',
            'transaction_date' => 'required|date',
            'reference_number' => 'nullable|string|max:255',
            'payment_method' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        // Verify the owner exists and user has access
        $owner = Owner::findOrFail($request->owner_id);
        if ($user->isEditor() && $owner->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $ownerEquity->update([
            'owner_id' => $request->owner_id,
            'transaction_type' => $request->transaction_type,
            'amount' => $request->amount,
            'transaction_date' => $request->transaction_date,
            'reference_number' => $request->reference_number,
            'payment_method' => $request->payment_method,
            'description' => $request->description,
            'notes' => $request->notes,
        ]);

        $ownerEquity->load(['owner', 'user']);

        return response()->json($ownerEquity);
    }

    /**
     * Remove the specified equity transaction
     */
    public function destroy(Request $request, OwnerEquity $ownerEquity): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->canDelete()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $ownerEquity->delete();

        return response()->json(['message' => 'Equity transaction deleted successfully']);
    }

    /**
     * Get equity summary for an owner
     */
    public function ownerSummary(Request $request, Owner $owner): JsonResponse
    {
        $user = $request->user();
        
        // Check if user can view this owner
        if ($user->isEditor() && $owner->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $summary = [
            'owner' => $owner,
            'total_equity' => $owner->total_equity,
            'total_contributions' => $owner->total_contributions,
            'total_withdrawals' => $owner->total_withdrawals,
            'total_distributions' => $owner->total_distributions,
            'recent_transactions' => $owner->equityTransactions()
                ->orderBy('transaction_date', 'desc')
                ->limit(5)
                ->get()
        ];

        return response()->json($summary);
    }

    /**
     * Get equity summary for all owners
     */
    public function summary(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $query = Owner::with('equityTransactions');
        
        // Editors can only see their own entries, admins can see all
        if ($user->isEditor()) {
            $query->where('user_id', $user->id);
        }

        $owners = $query->get();
        
        $summary = [
            'total_owners' => $owners->count(),
            'active_owners' => $owners->where('is_active', true)->count(),
            'total_equity' => $owners->sum('total_equity'),
            'total_contributions' => $owners->sum('total_contributions'),
            'total_withdrawals' => $owners->sum('total_withdrawals'),
            'total_distributions' => $owners->sum('total_distributions'),
            'owners' => $owners->map(function($owner) {
                return [
                    'id' => $owner->id,
                    'name' => $owner->name,
                    'ownership_percentage' => $owner->ownership_percentage,
                    'total_equity' => $owner->total_equity,
                    'is_active' => $owner->is_active,
                ];
            })
        ];

        return response()->json($summary);
    }
}
