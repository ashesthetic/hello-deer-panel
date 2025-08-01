<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProfitController extends Controller
{
    /**
     * Get profit percentages from configuration
     */
    public function getPercentages()
    {
        return response()->json([
            'fuel_percentage' => config('profit.fuel_percentage'),
            'tobacco_25_percentage' => config('profit.tobacco_25_percentage'),
            'tobacco_20_percentage' => config('profit.tobacco_20_percentage'),
            'lottery_percentage' => config('profit.lottery_percentage'),
            'prepay_percentage' => config('profit.prepay_percentage'),
            'store_sale_percentage' => config('profit.store_sale_percentage'),
        ]);
    }
} 