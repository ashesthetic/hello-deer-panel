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
        $sortBy = $request->input('sort_by', 'date');
        $sortDirection = $request->input('sort_direction', 'desc');
        $search = $request->input('search');
        $ownerId = $request->input('owner_id');
        $type = $request->input('type');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        // Build query
        $query = OwnerEquity::with(['owner']);
        
        // Filter by owner if specified
        if ($ownerId) {
            $query->where('owner_id', $ownerId);
        }
        
        // Filter by type if specified
        if ($type) {
            $query->where('type', $type);
        }
        
        // Filter by date range if specified
        if ($startDate && $endDate) {
            $query->whereBetween('date', [$startDate, $endDate]);
        }
        
        // Add search filter if provided
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                  ->orWhere('note', 'like', "%{$search}%")
                  ->orWhereHas('owner', function($ownerQuery) use ($search) {
                      $ownerQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }
        
        // Handle sorting
        $allowedSortFields = ['date', 'amount', 'type', 'created_at', 'updated_at'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'date';
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
            'type' => 'required|in:investment,withdrawal',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'description' => 'required|string|max:255',
            'note' => 'nullable|string',
        ]);

        // Verify the owner exists and user has access
        $owner = Owner::findOrFail($request->owner_id);
        if ($user->isEditor() && $owner->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $transaction = OwnerEquity::create([
            'owner_id' => $request->owner_id,
            'type' => $request->type,
            'amount' => $request->amount,
            'date' => $request->date,
            'description' => $request->description,
            'note' => $request->note,
        ]);

        $transaction->load(['owner']);

        return response()->json($transaction, 201);
    }

    /**
     * Display the specified equity transaction
     */
    public function show(Request $request, OwnerEquity $ownerEquity): JsonResponse
    {
        $user = $request->user();
        
        // Check if user can view this transaction
        $owner = $ownerEquity->owner;
        if ($user->isEditor() && $owner->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $ownerEquity->load(['owner']);

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
        
        $owner = $ownerEquity->owner;
        if ($user->isEditor() && $owner->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'owner_id' => 'required|exists:owners,id',
            'type' => 'required|in:investment,withdrawal',
            'amount' => 'required|numeric|min:0.01',
            'date' => 'required|date',
            'description' => 'required|string|max:255',
            'note' => 'nullable|string',
        ]);

        // Verify the new owner exists and user has access
        $newOwner = Owner::findOrFail($request->owner_id);
        if ($user->isEditor() && $newOwner->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $ownerEquity->update([
            'owner_id' => $request->owner_id,
            'type' => $request->type,
            'amount' => $request->amount,
            'date' => $request->date,
            'description' => $request->description,
            'note' => $request->note,
        ]);

        $ownerEquity->load(['owner']);

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

        $totalInvestments = $owner->equityTransactions()->where('type', 'investment')->sum('amount');
        $totalWithdrawals = $owner->equityTransactions()->where('type', 'withdrawal')->sum('amount');
        $totalEquity = $totalInvestments - $totalWithdrawals;

        $summary = [
            'owner' => $owner,
            'total_equity' => $totalEquity,
            'total_investments' => $totalInvestments,
            'total_withdrawals' => $totalWithdrawals,
            'recent_transactions' => $owner->equityTransactions()
                ->orderBy('date', 'desc')
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
        
        $totalInvestments = OwnerEquity::where('type', 'investment')->sum('amount');
        $totalWithdrawals = OwnerEquity::where('type', 'withdrawal')->sum('amount');
        $totalEquity = $totalInvestments - $totalWithdrawals;
        
        $summary = [
            'total_owners' => $owners->count(),
            'active_owners' => $owners->where('is_active', true)->count(),
            'total_equity' => $totalEquity,
            'total_investments' => $totalInvestments,
            'total_withdrawals' => $totalWithdrawals,
            'owners' => $owners->map(function($owner) {
                $investments = $owner->equityTransactions()->where('type', 'investment')->sum('amount');
                $withdrawals = $owner->equityTransactions()->where('type', 'withdrawal')->sum('amount');
                return [
                    'id' => $owner->id,
                    'name' => $owner->name,
                    'ownership_percentage' => $owner->ownership_percentage ?? 0,
                    'total_equity' => $investments - $withdrawals,
                    'is_active' => $owner->is_active ?? true,
                ];
            })
        ];

        return response()->json($summary);
    }
}
