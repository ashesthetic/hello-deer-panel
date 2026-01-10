<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FuelPrice;
use Illuminate\Http\Request;

class FuelPriceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $perPage = $request->input('per_page', 10);
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');
        
        // Build query based on user role
        $query = FuelPrice::with('user');
        
        // Apply user permissions - staff can only see their own entries
        $query->byUser($user);
        
        // Apply sorting
        $query->orderBy($sortBy, $sortDirection);
        
        $fuelPrices = $query->paginate($perPage);
        
        return response()->json($fuelPrices);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        
        $request->validate([
            'regular_87' => 'required|numeric|min:0|max:999.999',
            'midgrade_91' => 'required|numeric|min:0|max:999.999',
            'premium_94' => 'required|numeric|min:0|max:999.999',
            'diesel' => 'required|numeric|min:0|max:999.999',
        ]);

        $fuelPrice = FuelPrice::create([
            'regular_87' => $request->regular_87,
            'midgrade_91' => $request->midgrade_91,
            'premium_94' => $request->premium_94,
            'diesel' => $request->diesel,
            'user_id' => $user->id,
        ]);

        return response()->json([
            'message' => 'Fuel price created successfully',
            'data' => $fuelPrice->load('user')
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, FuelPrice $fuelPrice)
    {
        $user = $request->user();
        
        // Check if user can view this fuel price entry
        if ($user->isEditor() && $fuelPrice->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        return response()->json(['data' => $fuelPrice->load('user')]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, FuelPrice $fuelPrice)
    {
        $user = $request->user();
        
        // Check if user can edit this fuel price entry
        if ($user->isEditor() && $fuelPrice->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'regular_87' => 'required|numeric|min:0|max:999.999',
            'midgrade_91' => 'required|numeric|min:0|max:999.999',
            'premium_94' => 'required|numeric|min:0|max:999.999',
            'diesel' => 'required|numeric|min:0|max:999.999',
        ]);

        $fuelPrice->update([
            'regular_87' => $request->regular_87,
            'midgrade_91' => $request->midgrade_91,
            'premium_94' => $request->premium_94,
            'diesel' => $request->diesel,
        ]);

        return response()->json([
            'message' => 'Fuel price updated successfully',
            'data' => $fuelPrice->load('user')
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, FuelPrice $fuelPrice)
    {
        $user = $request->user();
        
        // Only admin can delete fuel prices
        if (!$user->canDelete()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $fuelPrice->delete();

        return response()->json([
            'message' => 'Fuel price deleted successfully'
        ]);
    }

    /**
     * Get latest fuel prices
     */
    public function latest(Request $request)
    {
        $user = $request->user();
        
        $query = FuelPrice::with('user');
        
        // Apply user permissions
        $query->byUser($user);
        
        $latestFuelPrice = $query->latest('created_at')->first();
        
        return response()->json(['data' => $latestFuelPrice]);
    }

    /**
     * Store a newly created resource for staff users.
     */
    public function storeForStaff(Request $request)
    {
        $user = $request->user();
        
        // Staff users are specifically allowed to create fuel prices through this endpoint
        if (!$user->isStaff()) {
            return response()->json(['message' => 'Unauthorized. This endpoint is only for staff users.'], 403);
        }

        $request->validate([
            'regular_87' => 'required|numeric|min:0|max:999.999',
            'midgrade_91' => 'required|numeric|min:0|max:999.999',
            'premium_94' => 'required|numeric|min:0|max:999.999',
            'diesel' => 'required|numeric|min:0|max:999.999',
        ]);

        $fuelPrice = FuelPrice::create([
            'regular_87' => $request->regular_87,
            'midgrade_91' => $request->midgrade_91,
            'premium_94' => $request->premium_94,
            'diesel' => $request->diesel,
            'user_id' => $user->id,
        ]);

        return response()->json([
            'message' => 'Fuel price created successfully',
            'data' => $fuelPrice->load('user')
        ], 201);
    }

    /**
     * Update a fuel price for staff users.
     */
    public function updateForStaff(Request $request, FuelPrice $fuelPrice)
    {
        $user = $request->user();
        
        // Staff users can only edit their own entries
        if (!$user->isStaff() || $fuelPrice->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'regular_87' => 'required|numeric|min:0|max:999.999',
            'midgrade_91' => 'required|numeric|min:0|max:999.999',
            'premium_94' => 'required|numeric|min:0|max:999.999',
            'diesel' => 'required|numeric|min:0|max:999.999',
        ]);

        $fuelPrice->update([
            'regular_87' => $request->regular_87,
            'midgrade_91' => $request->midgrade_91,
            'premium_94' => $request->premium_94,
            'diesel' => $request->diesel,
        ]);

        return response()->json([
            'message' => 'Fuel price updated successfully',
            'data' => $fuelPrice->load('user')
        ]);
    }
}
