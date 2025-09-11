<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use Illuminate\Http\Request;

class BankAccountController extends Controller
{
    /**
     * Display a listing of bank accounts
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $perPage = $request->input('per_page', 10);
        $sortBy = $request->input('sort_by', 'bank_name');
        $sortDirection = $request->input('sort_direction', 'asc');
        $search = $request->input('search');
        $accountType = $request->input('account_type');
        $isActive = $request->input('is_active');
        
        // Build query based on user role
        $query = BankAccount::with('user');
        
        // Editors can only see their own entries, admins can see all
        if ($user->isEditor()) {
            $query->where('user_id', $user->id);
        }
        
        // Add search filter if provided
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('bank_name', 'like', "%{$search}%")
                  ->orWhere('account_name', 'like', "%{$search}%")
                  ->orWhere('account_number', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }
        
        // Add account type filter
        if ($accountType) {
            $query->byAccountType($accountType);
        }
        
        // Add active status filter
        if ($isActive !== null) {
            $query->where('is_active', $isActive === 'true');
        }
        
        // Handle sorting
        $allowedSortFields = ['bank_name', 'account_name', 'account_type', 'balance', 'is_active', 'created_at', 'updated_at'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'bank_name';
        }
        $query->orderBy($sortBy, $sortDirection);
        
        $bankAccounts = $query->paginate($perPage);
        
        return response()->json($bankAccounts);
    }

    /**
     * Store a newly created bank account
     */
    public function store(Request $request)
    {
        $user = $request->user();
        
        if (!$user->canCreate()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'bank_name' => 'required|string|max:255',
            'account_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:255',
            'account_type' => 'required|in:Checking,Savings,Business,Credit,Investment',
            'routing_number' => 'nullable|string|max:255',
            'swift_code' => 'nullable|string|max:255',
            'currency' => 'nullable|string|size:3',
            'balance' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $data = [
            'bank_name' => $request->bank_name,
            'account_name' => $request->account_name,
            'account_number' => $request->account_number,
            'account_type' => $request->account_type,
            'routing_number' => $request->routing_number,
            'swift_code' => $request->swift_code,
            'currency' => $request->currency ?? 'CAD',
            'balance' => $request->balance ?? 0,
            'is_active' => $request->is_active ?? true,
            'notes' => $request->notes,
            'user_id' => $user->id,
        ];

        $bankAccount = BankAccount::create($data);

        return response()->json([
            'message' => 'Bank account created successfully',
            'data' => $bankAccount->load('user')
        ], 201);
    }

    /**
     * Display the specified bank account
     */
    public function show(Request $request, BankAccount $bankAccount)
    {
        $user = $request->user();
        
        // Check if user can view this bank account
        if ($user->isEditor() && $bankAccount->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($bankAccount->load('user'));
    }

    /**
     * Update the specified bank account
     */
    public function update(Request $request, BankAccount $bankAccount)
    {
        $user = $request->user();
        
        if (!$bankAccount->canBeUpdatedBy($user)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'bank_name' => 'required|string|max:255',
            'account_name' => 'required|string|max:255',
            'account_number' => 'required|string|max:255',
            'account_type' => 'required|in:Checking,Savings,Business,Credit,Investment',
            'routing_number' => 'nullable|string|max:255',
            'swift_code' => 'nullable|string|max:255',
            'currency' => 'nullable|string|size:3',
            'balance' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ]);

        $data = [
            'bank_name' => $request->bank_name,
            'account_name' => $request->account_name,
            'account_number' => $request->account_number,
            'account_type' => $request->account_type,
            'routing_number' => $request->routing_number,
            'swift_code' => $request->swift_code,
            'currency' => $request->currency ?? 'CAD',
            'balance' => $request->balance ?? 0,
            'is_active' => $request->is_active ?? true,
            'notes' => $request->notes,
        ];

        $bankAccount->update($data);

        return response()->json([
            'message' => 'Bank account updated successfully',
            'data' => $bankAccount->load('user')
        ]);
    }

    /**
     * Remove the specified bank account
     */
    public function destroy(Request $request, BankAccount $bankAccount)
    {
        $user = $request->user();
        
        if (!$bankAccount->canBeDeletedBy($user)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $bankAccount->delete();

        return response()->json([
            'message' => 'Bank account deleted successfully'
        ]);
    }

    /**
     * Get summary statistics for bank accounts
     */
    public function summary(Request $request)
    {
        $user = $request->user();
        
        $query = BankAccount::query();
        
        // Editors can only see their own entries, admins can see all
        if ($user->isEditor()) {
            $query->where('user_id', $user->id);
        }
        
        $totalAccounts = $query->count();
        $activeAccounts = $query->where('is_active', true)->count();
        $totalBalance = $query->where('is_active', true)->sum('balance');
        
        $accountsByType = $query->selectRaw('account_type, COUNT(*) as count, SUM(balance) as total_balance')
            ->where('is_active', true)
            ->groupBy('account_type')
            ->get();
            
        return response()->json([
            'total_accounts' => $totalAccounts,
            'active_accounts' => $activeAccounts,
            'inactive_accounts' => $totalAccounts - $activeAccounts,
            'total_balance' => $totalBalance,
            'formatted_total_balance' => '$' . number_format($totalBalance, 2),
            'accounts_by_type' => $accountsByType,
        ]);
    }
}
