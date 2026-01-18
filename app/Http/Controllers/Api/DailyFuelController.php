<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DailyFuel;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Utils\TimezoneUtil;
use App\Utils\FuelSheetUtil;

class DailyFuelController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $perPage = $request->input('per_page', 10);
        $sortBy = $request->input('sort_by', 'date');
        $sortDirection = $request->input('sort_direction', 'desc');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        
        // Build query based on user role
        $query = DailyFuel::with('user');
        
        // Editors can only see their own entries, admins can see all
        if ($user->isEditor()) {
            $query->where('user_id', $user->id);
        }
        
        // Add date range filter if provided
        if ($startDate) {
            $query->where('date', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('date', '<=', $endDate);
        }
        
        // Handle sorting for calculated fields
        if ($sortBy === 'total_quantity') {
            $query->orderByRaw('(regular_quantity + plus_quantity + sup_plus_quantity + diesel_quantity) ' . $sortDirection);
        } elseif ($sortBy === 'total_amount') {
            $query->orderByRaw('(regular_total_sale + plus_total_sale + sup_plus_total_sale + diesel_total_sale) ' . $sortDirection);
        } else {
            // Default sorting and other direct fields
            $allowedSortFields = ['date', 'regular_total_sale', 'plus_total_sale', 'sup_plus_total_sale', 'diesel_total_sale'];
            if (!in_array($sortBy, $allowedSortFields)) {
                $sortBy = 'date';
            }
            $query->orderBy($sortBy, $sortDirection);
        }
        
        $dailyFuels = $query->paginate($perPage);
        
        // Add calculated fields to each fuel entry
        $dailyFuels->getCollection()->transform(function ($fuel) {
            $fuel->total_quantity = ($fuel->regular_quantity ?? 0) + ($fuel->plus_quantity ?? 0) + ($fuel->sup_plus_quantity ?? 0) + ($fuel->diesel_quantity ?? 0);
            $fuel->total_amount = ($fuel->regular_total_sale ?? 0) + ($fuel->plus_total_sale ?? 0) + ($fuel->sup_plus_total_sale ?? 0) + ($fuel->diesel_total_sale ?? 0);
            $fuel->average_price = $fuel->total_quantity > 0 ? $fuel->total_amount / $fuel->total_quantity : 0;
            
            // Calculate individual price per liter fields
            $fuel->regular_price_per_liter = ($fuel->regular_quantity ?? 0) > 0 ? ($fuel->regular_total_sale ?? 0) / ($fuel->regular_quantity ?? 0) : 0;
            $fuel->plus_price_per_liter = ($fuel->plus_quantity ?? 0) > 0 ? ($fuel->plus_total_sale ?? 0) / ($fuel->plus_quantity ?? 0) : 0;
            $fuel->sup_plus_price_per_liter = ($fuel->sup_plus_quantity ?? 0) > 0 ? ($fuel->sup_plus_total_sale ?? 0) / ($fuel->sup_plus_quantity ?? 0) : 0;
            $fuel->diesel_price_per_liter = ($fuel->diesel_quantity ?? 0) > 0 ? ($fuel->diesel_total_sale ?? 0) / ($fuel->diesel_quantity ?? 0) : 0;
            
            return $fuel;
        });
        
        return response()->json($dailyFuels);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $user = $request->user();
        
        if (!$user->canCreate()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'date' => 'required|date|unique:daily_fuels,date',
            'regular_quantity' => 'nullable|numeric|min:0',
            'regular_total_sale' => 'nullable|numeric|min:0',
            'plus_quantity' => 'nullable|numeric|min:0',
            'plus_total_sale' => 'nullable|numeric|min:0',
            'sup_plus_quantity' => 'nullable|numeric|min:0',
            'sup_plus_total_sale' => 'nullable|numeric|min:0',
            'diesel_quantity' => 'nullable|numeric|min:0',
            'diesel_total_sale' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $data = $request->all();
        $data['user_id'] = $user->id; // Associate with current user
        
		$dailyFuel = DailyFuel::create($data);
		FuelSheetUtil::updateFuelVolumeAndSales( $data['date'], $data['regular_quantity'], $data['regular_total_sale'] );
        
        // Add calculated fields
        $dailyFuel->total_quantity = ($dailyFuel->regular_quantity ?? 0) + ($dailyFuel->plus_quantity ?? 0) + ($dailyFuel->sup_plus_quantity ?? 0) + ($dailyFuel->diesel_quantity ?? 0);
        $dailyFuel->total_amount = ($dailyFuel->regular_total_sale ?? 0) + ($dailyFuel->plus_total_sale ?? 0) + ($dailyFuel->sup_plus_total_sale ?? 0) + ($dailyFuel->diesel_total_sale ?? 0);
        $dailyFuel->average_price = $dailyFuel->total_quantity > 0 ? $dailyFuel->total_amount / $dailyFuel->total_quantity : 0;
        
        // Calculate individual price per liter fields
        $dailyFuel->regular_price_per_liter = ($dailyFuel->regular_quantity ?? 0) > 0 ? ($dailyFuel->regular_total_sale ?? 0) / ($dailyFuel->regular_quantity ?? 0) : 0;
        $dailyFuel->plus_price_per_liter = ($dailyFuel->plus_quantity ?? 0) > 0 ? ($dailyFuel->plus_total_sale ?? 0) / ($dailyFuel->plus_quantity ?? 0) : 0;
        $dailyFuel->sup_plus_price_per_liter = ($dailyFuel->sup_plus_quantity ?? 0) > 0 ? ($dailyFuel->sup_plus_total_sale ?? 0) / ($dailyFuel->sup_plus_quantity ?? 0) : 0;
        $dailyFuel->diesel_price_per_liter = ($dailyFuel->diesel_quantity ?? 0) > 0 ? ($dailyFuel->diesel_total_sale ?? 0) / ($dailyFuel->diesel_quantity ?? 0) : 0;

        return response()->json([
            'message' => 'Daily fuel created successfully',
            'data' => $dailyFuel
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, DailyFuel $dailyFuel)
    {
        $user = $request->user();
        
        // Check if user can view this specific fuel entry
        if ($user->isEditor() && $dailyFuel->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // Add calculated fields
        $dailyFuel->total_quantity = ($dailyFuel->regular_quantity ?? 0) + ($dailyFuel->plus_quantity ?? 0) + ($dailyFuel->sup_plus_quantity ?? 0) + ($dailyFuel->diesel_quantity ?? 0);
        $dailyFuel->total_amount = ($dailyFuel->regular_total_sale ?? 0) + ($dailyFuel->plus_total_sale ?? 0) + ($dailyFuel->sup_plus_total_sale ?? 0) + ($dailyFuel->diesel_total_sale ?? 0);
        $dailyFuel->average_price = $dailyFuel->total_quantity > 0 ? $dailyFuel->total_amount / $dailyFuel->total_quantity : 0;
        
        // Calculate individual price per liter fields
        $dailyFuel->regular_price_per_liter = ($dailyFuel->regular_quantity ?? 0) > 0 ? ($dailyFuel->regular_total_sale ?? 0) / ($dailyFuel->regular_quantity ?? 0) : 0;
        $dailyFuel->plus_price_per_liter = ($dailyFuel->plus_quantity ?? 0) > 0 ? ($dailyFuel->plus_total_sale ?? 0) / ($dailyFuel->plus_quantity ?? 0) : 0;
        $dailyFuel->sup_plus_price_per_liter = ($dailyFuel->sup_plus_quantity ?? 0) > 0 ? ($dailyFuel->sup_plus_total_sale ?? 0) / ($dailyFuel->sup_plus_quantity ?? 0) : 0;
        $dailyFuel->diesel_price_per_liter = ($dailyFuel->diesel_quantity ?? 0) > 0 ? ($dailyFuel->diesel_total_sale ?? 0) / ($dailyFuel->diesel_quantity ?? 0) : 0;

        return response()->json($dailyFuel);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DailyFuel $dailyFuel)
    {
        $user = $request->user();
        
        if (!$user->canUpdateDailyFuel($dailyFuel)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'date' => ['required', 'date', Rule::unique('daily_fuels')->ignore($dailyFuel->id)],
            'regular_quantity' => 'nullable|numeric|min:0',
            'regular_total_sale' => 'nullable|numeric|min:0',
            'plus_quantity' => 'nullable|numeric|min:0',
            'plus_total_sale' => 'nullable|numeric|min:0',
            'sup_plus_quantity' => 'nullable|numeric|min:0',
            'sup_plus_total_sale' => 'nullable|numeric|min:0',
            'diesel_quantity' => 'nullable|numeric|min:0',
            'diesel_total_sale' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $dailyFuel->update($request->all());
		FuelSheetUtil::updateFuelVolumeAndSales( $request->date, $request->regular_quantity, $request->regular_total_sale );
        
        // Add calculated fields
        $dailyFuel->total_quantity = ($dailyFuel->regular_quantity ?? 0) + ($dailyFuel->plus_quantity ?? 0) + ($dailyFuel->sup_plus_quantity ?? 0) + ($dailyFuel->diesel_quantity ?? 0);
        $dailyFuel->total_amount = ($dailyFuel->regular_total_sale ?? 0) + ($dailyFuel->plus_total_sale ?? 0) + ($dailyFuel->sup_plus_total_sale ?? 0) + ($dailyFuel->diesel_total_sale ?? 0);
        $dailyFuel->average_price = $dailyFuel->total_quantity > 0 ? $dailyFuel->total_amount / $dailyFuel->total_quantity : 0;
        
        // Calculate individual price per liter fields
        $dailyFuel->regular_price_per_liter = ($dailyFuel->regular_quantity ?? 0) > 0 ? ($dailyFuel->regular_total_sale ?? 0) / ($dailyFuel->regular_quantity ?? 0) : 0;
        $dailyFuel->plus_price_per_liter = ($dailyFuel->plus_quantity ?? 0) > 0 ? ($dailyFuel->plus_total_sale ?? 0) / ($dailyFuel->plus_quantity ?? 0) : 0;
        $dailyFuel->sup_plus_price_per_liter = ($dailyFuel->sup_plus_quantity ?? 0) > 0 ? ($dailyFuel->sup_plus_total_sale ?? 0) / ($dailyFuel->sup_plus_quantity ?? 0) : 0;
        $dailyFuel->diesel_price_per_liter = ($dailyFuel->diesel_quantity ?? 0) > 0 ? ($dailyFuel->diesel_total_sale ?? 0) / ($dailyFuel->diesel_quantity ?? 0) : 0;

        return response()->json([
            'message' => 'Daily fuel updated successfully',
            'data' => $dailyFuel
        ]);
    }

    /**
     * Get fuels for a specific month
     */
    public function getByMonth(Request $request, $year = null, $month = null)
    {
        $user = $request->user();
        $year = $year ?: TimezoneUtil::now()->format('Y');
        $month = $month ?: TimezoneUtil::now()->format('n');
        
        // Build query based on user role
        $query = DailyFuel::with('user');
        
        // Editors can only see their own entries, admins can see all
        if ($user->isEditor()) {
            $query->where('user_id', $user->id);
        }
        
        $dailyFuels = $query->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->orderBy('date', 'asc')
            ->get();
        
        // Add calculated fields to each fuel entry
        $dailyFuels->transform(function ($fuel) {
            $fuel->total_quantity = ($fuel->regular_quantity ?? 0) + ($fuel->plus_quantity ?? 0) + ($fuel->sup_plus_quantity ?? 0) + ($fuel->diesel_quantity ?? 0);
            $fuel->total_amount = ($fuel->regular_total_sale ?? 0) + ($fuel->plus_total_sale ?? 0) + ($fuel->sup_plus_total_sale ?? 0) + ($fuel->diesel_total_sale ?? 0);
            $fuel->average_price = $fuel->total_quantity > 0 ? $fuel->total_amount / $fuel->total_quantity : 0;
            
            // Calculate individual price per liter fields
            $fuel->regular_price_per_liter = ($fuel->regular_quantity ?? 0) > 0 ? ($fuel->regular_total_sale ?? 0) / ($fuel->regular_quantity ?? 0) : 0;
            $fuel->plus_price_per_liter = ($fuel->plus_quantity ?? 0) > 0 ? ($fuel->plus_total_sale ?? 0) / ($fuel->plus_quantity ?? 0) : 0;
            $fuel->sup_plus_price_per_liter = ($fuel->sup_plus_quantity ?? 0) > 0 ? ($fuel->sup_plus_total_sale ?? 0) / ($fuel->sup_plus_quantity ?? 0) : 0;
            $fuel->diesel_price_per_liter = ($fuel->diesel_quantity ?? 0) > 0 ? ($fuel->diesel_total_sale ?? 0) / ($fuel->diesel_quantity ?? 0) : 0;
            
            return $fuel;
        });
        
        return response()->json([
            'data' => $dailyFuels,
            'year' => $year,
            'month' => $month
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, DailyFuel $dailyFuel)
    {
        $user = $request->user();
        
        if (!$user->canDelete()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $dailyFuel->delete();

        return response()->json([
            'message' => 'Daily fuel deleted successfully'
        ]);
    }
}
