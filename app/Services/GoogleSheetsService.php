<?php

namespace App\Services;

use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;
use Illuminate\Support\Facades\Log;
use Exception;

class GoogleSheetsService
{
    private $client;
    private $service;
    private $spreadsheetId;
    
    public function __construct()
    {
        try {
            $this->client = new Client();
            $this->client->setAuthConfig(storage_path('app/google-service-account.json'));
            $this->client->addScope(Sheets::SPREADSHEETS);
            $this->client->setApplicationName(config('app.name', 'Laravel App'));
            
            $this->service = new Sheets($this->client);
            $this->spreadsheetId = config('google.spreadsheet_id');
            
            Log::info('GoogleSheetsService initialized successfully');
        } catch (Exception $e) {
            Log::error('Failed to initialize GoogleSheetsService: ' . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Update a specific cell in the Google Sheet
     *
     * @param string $cellRange (e.g., 'Sheet1!A1' or 'FuelPrices!B2')
     * @param mixed $value
     * @return bool
     */
    public function updateCell($cellRange, $value)
    {
        try {
            $body = new ValueRange([
                'values' => [[$value]]
            ]);
            
            $params = [
                'valueInputOption' => 'RAW'
            ];
            
            $result = $this->service->spreadsheets_values->update(
                $this->spreadsheetId,
                $cellRange,
                $body,
                $params
            );
            
            Log::info("Updated Google Sheet cell {$cellRange} with value: {$value}");
            return true;
            
        } catch (Exception $e) {
            Log::error("Failed to update Google Sheet cell {$cellRange}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update multiple cells in the Google Sheet
     *
     * @param array $updates Array of ['range' => 'value'] pairs
     * @return bool
     */
    public function updateMultipleCells($updates)
    {
        try {
            $data = [];
            foreach ($updates as $range => $value) {
                $data[] = new ValueRange([
                    'range' => $range,
                    'values' => [[$value]]
                ]);
            }
            
            $body = new \Google\Service\Sheets\BatchUpdateValuesRequest([
                'valueInputOption' => 'RAW',
                'data' => $data
            ]);
            
            $result = $this->service->spreadsheets_values->batchUpdate(
                $this->spreadsheetId,
                $body
            );
            
            Log::info('Updated multiple Google Sheet cells', $updates);
            return true;
            
        } catch (Exception $e) {
            Log::error('Failed to update multiple Google Sheet cells: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Read a specific cell value from the Google Sheet
     *
     * @param string $cellRange
     * @return mixed|null
     */
    public function readCell($cellRange)
    {
        try {
            $response = $this->service->spreadsheets_values->get(
                $this->spreadsheetId,
                $cellRange
            );
            
            $values = $response->getValues();
            return $values[0][0] ?? null;
            
        } catch (Exception $e) {
            Log::error("Failed to read Google Sheet cell {$cellRange}: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Update fuel price cells in the Google Sheet
     * This method is specifically designed for fuel volume price updates
     *
     * @param string $fuelType ('regular', 'premium', 'diesel')
     * @param float $price
     * @param string $date
     * @return bool
     */
    public function updateFuelPrice($fuelType, $price, $date = null)
    {
        try {
            $date = $date ?: now()->format('Y-m-d');
            
            // Define cell mappings for different fuel types
            // You can modify these cell references based on your actual Google Sheet structure
            $cellMappings = [
                'regular' => config('google.cells.regular_price', 'Sheet1!B2'),
                'premium' => config('google.cells.premium_price', 'Sheet1!C2'),
                'diesel' => config('google.cells.diesel_price', 'Sheet1!D2')
            ];
            
            if (!isset($cellMappings[$fuelType])) {
                Log::error("Unknown fuel type: {$fuelType}");
                return false;
            }
            
            $cellRange = $cellMappings[$fuelType];
            $formattedPrice = number_format($price, 3);
            
            $result = $this->updateCell($cellRange, $formattedPrice);
            
            if ($result) {
                Log::info("Updated {$fuelType} price to {$formattedPrice} in Google Sheet");
                
                // Also update the last updated timestamp
                $timestampCell = config('google.cells.last_updated', 'Sheet1!E2');
                $this->updateCell($timestampCell, $date);
            }
            
            return $result;
            
        } catch (Exception $e) {
            Log::error("Failed to update fuel price for {$fuelType}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update multiple fuel prices at once
     *
     * @param array $prices ['regular' => 1.234, 'premium' => 1.456, 'diesel' => 1.678]
     * @param string $date
     * @return bool
     */
    public function updateMultipleFuelPrices($prices, $date = null)
    {
        try {
            $date = $date ?: now()->format('Y-m-d');
            $updates = [];
            
            // Define cell mappings
            $cellMappings = [
                'regular' => config('google.cells.regular_price', 'Sheet1!B2'),
                'premium' => config('google.cells.premium_price', 'Sheet1!C2'),
                'diesel' => config('google.cells.diesel_price', 'Sheet1!D2')
            ];
            
            foreach ($prices as $fuelType => $price) {
                if (isset($cellMappings[$fuelType]) && $price !== null) {
                    $updates[$cellMappings[$fuelType]] = number_format($price, 3);
                }
            }
            
            // Add timestamp
            $timestampCell = config('google.cells.last_updated', 'Sheet1!E2');
            $updates[$timestampCell] = $date;
            
            return $this->updateMultipleCells($updates);
            
        } catch (Exception $e) {
            Log::error('Failed to update multiple fuel prices: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Test the connection to Google Sheets
     *
     * @return bool
     */
    public function testConnection()
    {
        try {
            $response = $this->service->spreadsheets->get($this->spreadsheetId);
            Log::info('Google Sheets connection test successful');
            return true;
        } catch (Exception $e) {
            Log::error('Google Sheets connection test failed: ' . $e->getMessage());
            return false;
        }
    }
}