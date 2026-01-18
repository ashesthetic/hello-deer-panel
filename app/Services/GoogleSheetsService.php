<?php

namespace App\Services;

use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;
use Illuminate\Support\Facades\Log;
use Exception;
use Carbon\Carbon;

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
     */
    public function updateCell(string $cellRange, $value): bool
    {
        try {
            $body = new ValueRange([
                'values' => [[$value]]
            ]);

            $params = [
                'valueInputOption' => 'RAW'
            ];

            $this->service->spreadsheets_values->update(
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
     */
    public function updateMultipleCells(array $updates): bool
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

            $this->service->spreadsheets_values->batchUpdate(
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
     */
    public function readCell(string $cellRange)
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
     * Get month sheet name from date
     */
    private function getMonthSheetName(string $date): string
    {
        return Carbon::parse($date)->format('F'); // January, February, etc.
    }

    /**
     * Find row number by Sales Date in column A
     */
    private function findRowByDate(string $sheetName, string $date): ?int
    {
        $range = "{$sheetName}!A:A";
        $response = $this->service->spreadsheets_values->get(
            $this->spreadsheetId,
            $range
        );

        $values = $response->getValues();
        if (!$values) return null;

        $targetDate = Carbon::parse($date)->format('Y-m-d');

        foreach ($values as $index => $row) {
            if (!isset($row[0])) continue;

            try {
                $sheetDate = Carbon::parse($row[0])->format('Y-m-d');
                if ($sheetDate === $targetDate) {
                    return $index + 1; // Google Sheets rows are 1-based
                }
            } catch (Exception $e) {
                continue;
            }
        }

        return null;
    }

    /**
     * Find column letter by header name in row 1
     */
    private function findColumnByHeader(string $sheetName, string $header): ?string
    {
        $range = "{$sheetName}!1:1";
        $response = $this->service->spreadsheets_values->get(
            $this->spreadsheetId,
            $range
        );

        $headers = $response->getValues()[0] ?? [];
        foreach ($headers as $index => $value) {
            if (trim($value) === trim($header)) {
                return $this->columnIndexToLetter($index + 1);
            }
        }

        return null;
    }

    /**
     * Convert column index (1-based) to letter (A, B, ..., AA)
     */
    private function columnIndexToLetter(int $index): string
    {
        $letter = '';
        while ($index > 0) {
            $temp = ($index - 1) % 26;
            $letter = chr($temp + 65) . $letter;
            $index = (int)(($index - $temp - 1) / 26);
        }
        return $letter;
    }

    /**
     * Update a value by date and column header
     */
    public function updateByDateAndHeader(string $date, string $columnHeader, $value): bool
    {
        try {
            $sheetName = $this->getMonthSheetName($date);
            $row = $this->findRowByDate($sheetName, $date);
            if (!$row) {
                Log::error("Date {$date} not found in sheet {$sheetName}");
                return false;
            }

            $column = $this->findColumnByHeader($sheetName, $columnHeader);
            if (!$column) {
                Log::error("Column {$columnHeader} not found in sheet {$sheetName}");
                return false;
            }

            $range = "{$sheetName}!{$column}{$row}";
            return $this->updateCell($range, $value);

        } catch (Exception $e) {
            Log::error("Failed updateByDateAndHeader: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update fuel price for a specific date
     */
    public function updateFuelPrice(string $fuelType, float $price, string $date = null): bool
    {
        $date = $date ?: now()->format('Y-m-d');

        // Map fuel types to your sheet column headers
        $headers = [
            'regular' => 'Unit Price ($) Per Liter without  GST',
            'premium' => 'Unit Price ($) Per Liter with  GST',
            'diesel' => 'Open Street Price ($ Per Liter)'
        ];

        if (!isset($headers[$fuelType])) {
            Log::error("Unknown fuel type: {$fuelType}");
            return false;
        }

        return $this->updateByDateAndHeader($date, $headers[$fuelType], number_format($price, 3));
    }

    /**
     * Update multiple fuel prices for the same date
     */
    public function updateMultipleFuelPrices(array $prices, string $date = null): bool
    {
        $date = $date ?: now()->format('Y-m-d');
        $success = true;

        foreach ($prices as $fuelType => $price) {
            if ($price !== null) {
                $result = $this->updateFuelPrice($fuelType, $price, $date);
                if (!$result) $success = false;
            }
        }

        return $success;
    }

    /**
     * Test connection to Google Sheets
     */
    public function testConnection(): bool
    {
        try {
            $this->service->spreadsheets->get($this->spreadsheetId);
            Log::info('Google Sheets connection test successful');
            return true;
        } catch (Exception $e) {
            Log::error('Google Sheets connection test failed: ' . $e->getMessage());
            return false;
        }
    }
}