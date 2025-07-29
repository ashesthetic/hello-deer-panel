<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Provider;
use Illuminate\Http\Request;

class ProviderController extends Controller
{
    /**
     * Display a listing of providers
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $perPage = $request->input('per_page', 10);
        $sortBy = $request->input('sort_by', 'name');
        $sortDirection = $request->input('sort_direction', 'asc');
        $search = $request->input('search');
        
        // Build query based on user role
        $query = Provider::with('user');
        
        // Editors can only see their own entries, admins can see all
        if ($user->isEditor()) {
            $query->where('user_id', $user->id);
        }
        
        // Add search filter if provided
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('service', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }
        
        // Handle sorting
        $allowedSortFields = ['name', 'service', 'payment_method', 'email', 'phone', 'created_at', 'updated_at'];
        if (!in_array($sortBy, $allowedSortFields)) {
            $sortBy = 'name';
        }
        $query->orderBy($sortBy, $sortDirection);
        
        $providers = $query->paginate($perPage);
        
        return response()->json($providers);
    }

    /**
     * Store a newly created provider
     */
    public function store(Request $request)
    {
        $user = $request->user();
        
        if (!$user->canCreate()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'service' => 'required|string|max:255',
            'payment_method' => 'required|in:PAD,Credit Card,E-transfer,Direct Deposit',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
        ]);

        $provider = Provider::create([
            'name' => $request->name,
            'service' => $request->service,
            'payment_method' => $request->payment_method,
            'phone' => $request->phone,
            'email' => $request->email,
            'user_id' => $user->id,
        ]);

        return response()->json([
            'message' => 'Provider created successfully',
            'data' => $provider->load('user')
        ], 201);
    }

    /**
     * Display the specified provider
     */
    public function show(Request $request, Provider $provider)
    {
        $user = $request->user();
        
        // Check if user can view this provider
        if ($user->isEditor() && $provider->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($provider->load('user'));
    }

    /**
     * Update the specified provider
     */
    public function update(Request $request, Provider $provider)
    {
        $user = $request->user();
        
        if (!$provider->canBeUpdatedBy($user)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'service' => 'required|string|max:255',
            'payment_method' => 'required|in:PAD,Credit Card,E-transfer,Direct Deposit',
            'phone' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
        ]);

        $provider->update([
            'name' => $request->name,
            'service' => $request->service,
            'payment_method' => $request->payment_method,
            'phone' => $request->phone,
            'email' => $request->email,
        ]);

        return response()->json([
            'message' => 'Provider updated successfully',
            'data' => $provider->load('user')
        ]);
    }

    /**
     * Remove the specified provider
     */
    public function destroy(Request $request, Provider $provider)
    {
        $user = $request->user();
        
        if (!$provider->canBeDeletedBy($user)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $provider->delete();

        return response()->json([
            'message' => 'Provider deleted successfully'
        ]);
    }
}
