<?php

namespace App\Console\Commands;

use App\Mail\GasBuddyPriceAlert;
use App\Models\FuelPrice;
use App\Models\GasBuddyStation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class FetchGasBuddyPrices extends Command
{
    protected $signature   = 'gasbuddy:fetch';
    protected $description = 'Fetch live gas prices from GasBuddy for Red Deer and store in DB';

    // Coordinates for 140 Erickson Dr, Red Deer, T4R 2C3
    private const LAT = 52.2661104;
    private const LNG = -113.7738163;

    private const GRAPHQL_URL   = 'https://www.gasbuddy.com/graphql';
    private const ALERT_EMAIL   = 'thedeerhubcinc@gmail.com';
    private const ALERT_THRESHOLD = 3; // how many stations must be cheaper to trigger alert

    public function handle(): int
    {
        $this->info('Fetching GasBuddy prices...');

        try {
            $stations = $this->fetchFromGasBuddy();
        } catch (\Exception $e) {
            $this->error('Failed to fetch from GasBuddy: ' . $e->getMessage());
            Log::error('GasBuddy fetch command failed: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->info('Fetched ' . count($stations) . ' stations (total available: ' . ($this->lastCount ?? '?') . '). Upserting to DB...');
        $now = now();

        foreach ($stations as $stationData) {
            $prices = $this->extractPrices($stationData['prices'] ?? []);

            GasBuddyStation::updateOrCreate(
                ['gasbuddy_station_id' => (string) $stationData['id']],
                [
                    'name'                    => $stationData['name'],
                    'address_line1'           => trim($stationData['address']['line1'] ?? ''),
                    'address_line2'           => trim($stationData['address']['line2'] ?? ''),
                    'distance'                => $stationData['distance'],
                    'regular_gas'             => $prices['regular_gas']['price'],
                    'midgrade_gas'            => $prices['midgrade_gas']['price'],
                    'premium_gas'             => $prices['premium_gas']['price'],
                    'diesel'                  => $prices['diesel']['price'],
                    'regular_gas_posted_at'   => $prices['regular_gas']['posted_at'],
                    'midgrade_gas_posted_at'  => $prices['midgrade_gas']['posted_at'],
                    'premium_gas_posted_at'   => $prices['premium_gas']['posted_at'],
                    'diesel_posted_at'        => $prices['diesel']['posted_at'],
                    'last_fetched_at'         => $now,
                ]
            );
        }

        $this->info('DB updated. Checking price alert...');
        $this->checkPriceAlert();

        $this->info('Done.');
        return self::SUCCESS;
    }

    private function extractPrices(array $prices): array
    {
        $result = [
            'regular_gas'  => ['price' => null, 'posted_at' => null],
            'midgrade_gas' => ['price' => null, 'posted_at' => null],
            'premium_gas'  => ['price' => null, 'posted_at' => null],
            'diesel'       => ['price' => null, 'posted_at' => null],
        ];

        foreach ($prices as $p) {
            $key = $p['fuelProduct'] ?? null;
            if (!$key || !isset($result[$key])) continue;

            $priceVal  = $p['credit']['price']      ?? $p['cash']['price']      ?? null;
            $postedAt  = $p['credit']['postedTime']  ?? $p['cash']['postedTime']  ?? null;

            // Treat 0 as no price (GasBuddy returns 0 when price is unknown)
            if ($priceVal !== null && (float) $priceVal <= 0) {
                $priceVal = null;
                $postedAt = null;
            }

            $result[$key] = [
                'price'     => $priceVal,
                'posted_at' => $postedAt ? \Carbon\Carbon::parse($postedAt) : null,
            ];
        }

        return $result;
    }

    private function checkPriceAlert(): void
    {
        $ourLatest = FuelPrice::latest('created_at')->first();
        if (!$ourLatest) {
            $this->warn('No our own fuel price found — skipping alert check.');
            return;
        }

        $ourPrice = (float) $ourLatest->regular_87;

        // Only consider stations within 3 miles
        $cheaperStations = GasBuddyStation::whereNotNull('regular_gas')
            ->where('regular_gas', '<', $ourPrice)
            ->whereRaw('CAST(distance AS DECIMAL(8,4)) <= 3')
            ->orderByRaw('CAST(distance AS DECIMAL(8,4)) ASC')
            ->get();

        $count = $cheaperStations->count();
        $this->info("{$count} station(s) within 3mi are cheaper than our price of {$ourPrice}¢/L.");

        if ($count >= self::ALERT_THRESHOLD) {
            $this->warn("Threshold reached ({$count} >= " . self::ALERT_THRESHOLD . "). Sending alert email...");
            Mail::to(self::ALERT_EMAIL)->send(new GasBuddyPriceAlert(
                ourPrice:        $ourPrice,
                cheaperStations: $cheaperStations->all(),
                totalCheaper:    $count,
            ));
            $this->info('Alert email sent to ' . self::ALERT_EMAIL);
            Log::info("GasBuddy price alert sent: {$count} stations cheaper than {$ourPrice}¢/L.");
        }
    }

    private int $lastCount = 0;

    private function fetchFromGasBuddy(): array
    {
        $token = '1.' . Str::random(16);

        $query = <<<'GQL'
query LocationBySearchTerm($fuel: Int, $maxAge: Int, $lat: Float, $lng: Float, $limit: Int) {
  locationBySearchTerm(lat: $lat, lng: $lng, priority: "locality") {
    displayName
    stations(fuel: $fuel, maxAge: $maxAge, lat: $lat, lng: $lng, priority: "locality", limit: $limit) {
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
            'gbcsrf'                   => $token,
            'Origin'                   => 'https://www.gasbuddy.com',
            'Referer'                  => 'https://www.gasbuddy.com/home?search=red+deer&fuel=1&method=all&maxAge=0',
            'User-Agent'               => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36',
        ])->post(self::GRAPHQL_URL, [
            'operationName' => 'LocationBySearchTerm',
            'variables'     => ['fuel' => 1, 'maxAge' => 0, 'lat' => self::LAT, 'lng' => self::LNG, 'limit' => 100],
            'query'         => $query,
        ]);

        if (!$response->successful()) {
            throw new \RuntimeException('GasBuddy returned HTTP ' . $response->status());
        }

        $body = $response->json();
        if (isset($body['errors'])) {
            throw new \RuntimeException(json_encode($body['errors']));
        }

        $this->lastCount = $body['data']['locationBySearchTerm']['stations']['count'] ?? 0;
        return $body['data']['locationBySearchTerm']['stations']['results'] ?? [];
    }
}
