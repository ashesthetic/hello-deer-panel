<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FuelPrice;
use Illuminate\Http\JsonResponse;

class PublicDataController extends Controller
{
    /**
     * Get public data including latest fuel prices
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        try {
            // Get the latest fuel price entry
            $latestFuelPrice = FuelPrice::latest('created_at')->first();

            $data = [
                'fuel' => [
                    'regular' => $latestFuelPrice ? $latestFuelPrice->regular_87 : null,
                    'midgrade' => $latestFuelPrice ? $latestFuelPrice->midgrade_91 : null,
                    'premium' => $latestFuelPrice ? $latestFuelPrice->premium_94 : null,
                    'diesel' => $latestFuelPrice ? $latestFuelPrice->diesel : null,
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch public data',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }
}