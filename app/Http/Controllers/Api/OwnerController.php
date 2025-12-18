<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Owner;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class OwnerController extends Controller
{
    /**
     * Display a listing of owners
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = $request->input('per_page', 10);
        $sortBy = $request->input('sort_by', 'name');
        $sortDirection = $request->input('sort_direction', 'asc');
        $search = $request->input('search');
        
        // Build query based on user role
        $query = Owner::with('user');
        
        // Editors can only see their own entries, admins can see all
        if ($user->isEditor()) {
            $query->where('user_id', $user->id);
        }
        
        // Add search filter if provided
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('notes', 'like', "%{$search}%");
            });
        }
        
        // Handle sorting
        $allowedSortFields = ['name', 'email', 'ownership_percentage', 'is_active', 'created_at', 'updated_at'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'name';
        }
        $query->orderBy($sortBy, $sortDirection);
        
        $owners = $query->paginate($perPage);
        
        return response()->json($owners);
    }

    /**
     * Store a newly created owner
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->canCreate()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:10',
            'country' => 'nullable|string|max:255',
            'ownership_percentage' => 'required|numeric|min:0|max:100',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $owner = Owner::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'city' => $request->city,
            'province' => $request->province,
            'postal_code' => $request->postal_code,
            'country' => $request->country ?? 'Canada',
            'ownership_percentage' => $request->ownership_percentage,
            'notes' => $request->notes,
            'is_active' => $request->input('is_active', true),
            'user_id' => $user->id,
        ]);

        $owner->load('user');

        return response()->json($owner, 201);
    }

    /**
     * Display the specified owner
     */
    public function show(Request $request, Owner $owner): JsonResponse
    {
        $user = $request->user();
        
        // Check if user can view this owner
        if ($user->isEditor() && $owner->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $owner->load(['user', 'equityTransactions' => function($query) {
            $query->orderBy('transaction_date', 'desc')->limit(10);
        }]);

        return response()->json($owner);
    }

    /**
     * Update the specified owner
     */
    public function update(Request $request, Owner $owner): JsonResponse
    {
        $user = $request->user();
        
        // Check if user can update this owner
        if (!$user->canUpdate()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        if ($user->isEditor() && $owner->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:255',
            'province' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:10',
            'country' => 'nullable|string|max:255',
            'ownership_percentage' => 'required|numeric|min:0|max:100',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $owner->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'city' => $request->city,
            'province' => $request->province,
            'postal_code' => $request->postal_code,
            'country' => $request->country ?? 'Canada',
            'ownership_percentage' => $request->ownership_percentage,
            'notes' => $request->notes,
            'is_active' => $request->input('is_active', true),
        ]);

        $owner->load('user');

        return response()->json($owner);
    }

    /**
     * Remove the specified owner
     */
    public function destroy(Request $request, Owner $owner): JsonResponse
    {
        $user = $request->user();
        
        if (!$user->canDelete()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Check if owner has any equity transactions
        if ($owner->equityTransactions()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete owner with existing equity transactions. Please delete all transactions first.'
            ], 422);
        }

        $owner->delete();

        return response()->json(['message' => 'Owner deleted successfully']);
    }
}
