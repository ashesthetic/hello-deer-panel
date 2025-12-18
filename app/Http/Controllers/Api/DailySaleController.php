<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DailySale;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DailySaleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        $dailySales = DailySale::orderBy('date', 'desc')->paginate($perPage);
        
        // Add calculated fields to each sale
        $dailySales->getCollection()->transform(function ($sale) {
            $sale->total_product_sale = $sale->fuel_sale + $sale->store_sale + $sale->gst;
            $sale->total_counter_sale = $sale->card + $sale->cash + $sale->coupon + $sale->delivery;
            $sale->grand_total = $sale->total_product_sale + $sale->total_counter_sale;
            return $sale;
        });
        
        return response()->json($dailySales);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
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

        $dailySale = DailySale::create($request->all());
        
        // Add calculated fields
        $dailySale->total_product_sale = $dailySale->fuel_sale + $dailySale->store_sale + $dailySale->gst;
        $dailySale->total_counter_sale = $dailySale->card + $dailySale->cash + $dailySale->coupon + $dailySale->delivery;
        $dailySale->grand_total = $dailySale->total_product_sale + $dailySale->total_counter_sale;

        return response()->json([
            'message' => 'Daily sale created successfully',
            'data' => $dailySale
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(DailySale $dailySale)
    {
        // Add calculated fields
        $dailySale->total_product_sale = $dailySale->fuel_sale + $dailySale->store_sale + $dailySale->gst;
        $dailySale->total_counter_sale = $dailySale->card + $dailySale->cash + $dailySale->coupon + $dailySale->delivery;
        $dailySale->grand_total = $dailySale->total_product_sale + $dailySale->total_counter_sale;
        
        return response()->json($dailySale);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, DailySale $dailySale)
    {
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
        $year = $year ?: date('Y');
        $month = $month ?: date('n');
        
        $dailySales = DailySale::whereYear('date', $year)
            ->whereMonth('date', $month)
            ->orderBy('date', 'asc')
            ->get();
        
        // Add calculated fields to each sale
        $dailySales->transform(function ($sale) {
            $sale->total_product_sale = $sale->fuel_sale + $sale->store_sale + $sale->gst;
            $sale->total_counter_sale = $sale->card + $sale->cash + $sale->coupon + $sale->delivery;
            $sale->grand_total = $sale->total_product_sale + $sale->total_counter_sale;
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
    public function destroy(DailySale $dailySale)
    {
        $dailySale->delete();

        return response()->json([
            'message' => 'Daily sale deleted successfully'
        ]);
    }
}
