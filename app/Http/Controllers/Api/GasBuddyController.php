<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GasBuddyStation;
use Illuminate\Http\Request;

class GasBuddyController extends Controller
{
    /**
     * Return stored GasBuddy prices from the DB, sorted by distance.
     */
    public function getRedDeerPrices(Request $request)
    {
        $stations = GasBuddyStation::orderByRaw('CAST(distance AS DECIMAL(8,4)) ASC')->get();

        if ($stations->isEmpty()) {
            return response()->json([
                'success'      => false,
                'message'      => 'No GasBuddy data yet. Run: php artisan gasbuddy:fetch',
                'display_name' => 'Red Deer, AB',
                'count'        => 0,
                'stations'     => [],
            ]);
        }

        $mapped = $stations->map(fn ($s) => [
            'id'       => $s->gasbuddy_station_id,
            'name'     => $s->name,
            'address'  => ['line1' => $s->address_line1, 'line2' => $s->address_line2],
            'distance' => $s->distance,
            'prices'   => [
                ['fuelProduct' => 'regular_gas',  'credit' => ['price' => ($s->regular_gas  > 0) ? (float)$s->regular_gas  : null, 'postedTime' => $s->regular_gas_posted_at]],
                ['fuelProduct' => 'midgrade_gas', 'credit' => ['price' => ($s->midgrade_gas > 0) ? (float)$s->midgrade_gas : null, 'postedTime' => $s->midgrade_gas_posted_at]],
                ['fuelProduct' => 'premium_gas',  'credit' => ['price' => ($s->premium_gas  > 0) ? (float)$s->premium_gas  : null, 'postedTime' => $s->premium_gas_posted_at]],
                ['fuelProduct' => 'diesel',        'credit' => ['price' => ($s->diesel       > 0) ? (float)$s->diesel       : null, 'postedTime' => $s->diesel_posted_at]],

            ],
            'last_fetched_at' => $s->last_fetched_at,
        ]);

        return response()->json([
            'success'      => true,
            'display_name' => 'Red Deer, AB',
            'count'        => $stations->count(),
            'stations'     => $mapped,
            'last_fetched_at' => $stations->max('last_fetched_at'),
        ]);
    }
}

