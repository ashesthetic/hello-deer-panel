<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GasBuddyController extends Controller
{
    private const GRAPHQL_URL = 'https://www.gasbuddy.com/graphql';
    private const CACHE_KEY   = 'gasbuddy_red_deer_prices';
    private const CACHE_TTL   = 300; // 5 minutes

    /**
     * Fetch live gas prices for Red Deer from GasBuddy.
     */
    public function getRedDeerPrices(Request $request)
    {
        try {
            $data = Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
                return $this->fetchFromGasBuddy();
            });

            return response()->json($data);
        } catch (\Exception $e) {
            Log::error('GasBuddy fetch error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch GasBuddy prices: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Query GasBuddy GraphQL sorted by distance from 140 Erickson Dr, Red Deer.
     * The gbcsrf header just needs to be present — any value is accepted.
     */
    private function fetchFromGasBuddy(): array
    {
        $csrfToken = '1.' . Str::random(16);

        // Coordinates for 140 Erickson Dr, Red Deer, T4R 2C3
        $lat = 52.2661104;
        $lng = -113.7738163;

        $query = <<<'GQL'
query LocationBySearchTerm($fuel: Int, $maxAge: Int, $lat: Float, $lng: Float) {
  locationBySearchTerm(lat: $lat, lng: $lng, priority: "locality") {
    displayName
    stations(fuel: $fuel, maxAge: $maxAge, lat: $lat, lng: $lng, priority: "locality") {
      count
      results {
        id
        name
        address { line1 line2 }
        distance
        prices {
          cash { price postedTime }
          credit { price postedTime }
          fuelProduct
        }
      }
    }
  }
}
GQL;

        $response = Http::withHeaders([
            'Content-Type'             => 'application/json',
            'Accept'                   => '*/*',
            'apollo-require-preflight' => 'true',
            'gbcsrf'                   => $csrfToken,
            'Origin'                   => 'https://www.gasbuddy.com',
            'Referer'                  => 'https://www.gasbuddy.com/home?search=red+deer&fuel=1&method=all&maxAge=0',
            'User-Agent'               => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        ])->post(self::GRAPHQL_URL, [
            'operationName' => 'LocationBySearchTerm',
            'variables'     => [
                'fuel'   => 1,
                'maxAge' => 0,
                'lat'    => $lat,
                'lng'    => $lng,
            ],
            'query' => $query,
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException('GasBuddy GraphQL request failed with HTTP ' . $response->status());
        }

        $body = $response->json();

        if (isset($body['errors'])) {
            throw new \RuntimeException('GasBuddy GraphQL error: ' . json_encode($body['errors']));
        }

        $location = $body['data']['locationBySearchTerm'] ?? null;
        if (!$location) {
            throw new \RuntimeException('No location data returned from GasBuddy');
        }

        return [
            'success'      => true,
            'display_name' => $location['displayName'] ?? 'Red Deer',
            'count'        => $location['stations']['count'] ?? 0,
            'stations'     => $location['stations']['results'] ?? [],
        ];
    }
}

