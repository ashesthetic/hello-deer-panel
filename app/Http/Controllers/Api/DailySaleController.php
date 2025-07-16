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
    public function index()
    {
        $dailySales = DailySale::orderBy('date', 'desc')->paginate(10);
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
            'notes' => 'nullable|string',
        ]);

        $dailySale = DailySale::create($request->all());

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
            'notes' => 'nullable|string',
        ]);

        $dailySale->update($request->all());

        return response()->json([
            'message' => 'Daily sale updated successfully',
            'data' => $dailySale
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
