<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DailySale;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Utils\TimezoneUtil;

class DailySaleController extends Controller
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
        $query = DailySale::with('user');
        
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
        if ($sortBy === 'total_product_sale') {
            $query->orderByRaw('(fuel_sale + store_sale + gst) ' . $sortDirection);
        } elseif ($sortBy === 'total_counter_sale') {
            $query->orderByRaw('(card + cash + coupon + delivery) ' . $sortDirection);
        } elseif ($sortBy === 'reported_total') {
            $query->orderBy('reported_total', $sortDirection);
        } else {
            // Default sorting and other direct fields
            $allowedSortFields = ['date', 'fuel_sale', 'store_sale', 'gst', 'card', 'cash', 'coupon', 'delivery'];
            if (!in_array($sortBy, $allowedSortFields)) {
                $sortBy = 'date';
            }
            $query->orderBy($sortBy, $sortDirection);
        }
        
        $dailySales = $query->paginate($perPage);
        
        // Add calculated fields to each sale
        $dailySales->getCollection()->transform(function ($sale) {
            $sale->total_product_sale = $sale->fuel_sale + $sale->store_sale + $sale->gst;
            $sale->total_counter_sale = $sale->card + $sale->cash + $sale->coupon + $sale->delivery;
            $sale->grand_total = $sale->total_product_sale + $sale->total_counter_sale;
            // Ensure reported_total is not null
            $sale->reported_total = $sale->reported_total ?? 0;
            return $sale;
        });
        
        return response()->json($dailySales);
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
            'date' => 'required|date|unique:daily_sales,date',
            'fuel_sale' => 'required|numeric|min:0',
            'store_sale' => 'required|numeric|min:0',
            'gst' => 'required|numeric|min:0',
            'card' => 'required|numeric|min:0',
            'cash' => 'required|numeric|min:0',
            'coupon' => 'required|numeric|min:0',
            'delivery' => 'required|numeric|min:0',
            'reported_total' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $data = $request->all();
        $data['user_id'] = $user->id; // Associate with current user
        
        $dailySale = DailySale::create($data);
        
        // Add calculated fields
        $dailySale->total_product_sale = $dailySale->fuel_sale + $dailySale->store_sale + $dailySale->gst;
        $dailySale->total_counter_sale = $dailySale->card + $dailySale->cash + $dailySale->coupon + $dailySale->delivery;
        $dailySale->grand_total = $dailySale->total_product_sale + $dailySale->total_counter_sale;
        // Ensure reported_total is not null
        $dailySale->reported_total = $dailySale->reported_total ?? 0;

        return response()->json([
            'message' => 'Daily sale created successfully',
            'data' => $dailySale
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, DailySale $dailySale)
    {
        $user = $request->user();
        
        // Check if user can view this specific sale
        if ($user->isEditor() && $dailySale->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        
        // Add calculated fields
        $dailySale->total_product_sale = $dailySale->fuel_sale + $dailySale->store_sale + $dailySale->gst;
        $dailySale->total_counter_sale = $dailySale->card + $dailySale->cash + $dailySale->coupon + $dailySale->delivery;
        $dailySale->grand_total = $dailySale->total_product_sale + $dailySale->total_counter_sale;
        // Ensure reported_total is not null
        $dailySale->reported_total = $dailySale->reported_total ?? 0;
        
        return response()->json($dailySale);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DailySale $dailySale)
    {
        $user = $request->user();
        
        if (!$user->canUpdateDailySale($dailySale)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'date' => ['required', 'date', Rule::unique('daily_sales')->ignore($dailySale->id)],
            'fuel_sale' => 'required|numeric|min:0',
            'store_sale' => 'required|numeric|min:0',
            'gst' => 'required|numeric|min:0',
            'card' => 'required|numeric|min:0',
            'cash' => 'required|numeric|min:0',
            'coupon' => 'required|numeric|min:0',
            'delivery' => 'required|numeric|min:0',
            'reported_total' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ]);

        $dailySale->update($request->all());
        
        // Add calculated fields
        $dailySale->total_product_sale = $dailySale->fuel_sale + $dailySale->store_sale + $dailySale->gst;
        $dailySale->total_counter_sale = $dailySale->card + $dailySale->cash + $dailySale->coupon + $dailySale->delivery;
        $dailySale->grand_total = $dailySale->total_product_sale + $dailySale->total_counter_sale;
        // Ensure reported_total is not null
        $dailySale->reported_total = $dailySale->reported_total ?? 0;

        return response()->json([
            'message' => 'Daily sale updated successfully',
            'data' => $dailySale
        ]);
    }

    /**
     * Get sales for a specific month
     */
    public function getByMonth(Request $request, $year = null, $month = null)
    {
        $user = $request->user();
        $year = $year ?: TimezoneUtil::now()->format('Y');
        $month = $month ?: TimezoneUtil::now()->format('n');
        
        // Build query based on user role
        $query = DailySale::with('user');
        
        // Editors can only see their own entries, admins can see all
        if ($user->isEditor()) {
            $query->where('user_id', $user->id);
        }
        
        $dailySales = $query->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->orderBy('date', 'asc')
            ->get();
        
        // Add calculated fields to each sale
        $dailySales->transform(function ($sale) {
            $sale->total_product_sale = $sale->fuel_sale + $sale->store_sale + $sale->gst;
            $sale->total_counter_sale = $sale->card + $sale->cash + $sale->coupon + $sale->delivery;
            $sale->grand_total = $sale->total_product_sale + $sale->total_counter_sale;
            // Ensure reported_total is not null
            $sale->reported_total = $sale->reported_total ?? 0;
            return $sale;
        });
        
        return response()->json([
            'data' => $dailySales,
            'year' => $year,
            'month' => $month
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, DailySale $dailySale)
    {
        $user = $request->user();
        
        if (!$user->canDelete()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $dailySale->delete();

        return response()->json([
            'message' => 'Daily sale deleted successfully'
        ]);
    }
}
