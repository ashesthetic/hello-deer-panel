<?php

namespace App\Utils;

use Google\Client;
use Google\Service\Sheets;
use Google\Service\Sheets\ValueRange;
use Exception;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class FuelSheetUtil
{
    private static ?Client $client = null;
    private static ?Sheets $service = null;
    private static ?string $spreadsheetId = null;

    private static function init(): void
    {
        if (self::$client && self::$service) return;

        try {
            // Log::info('Initializing Google Client for FuelSheetUtil...');
            self::$client = new Client();
            self::$client->setAuthConfig(storage_path('app/google-service-account.json'));
            self::$client->addScope(Sheets::SPREADSHEETS);
            self::$client->setApplicationName(config('app.name', 'Laravel App'));

            self::$service = new Sheets(self::$client);
            self::$spreadsheetId = config('google.spreadsheet_id');

            // Log::info('Google Client initialized successfully.');

        } catch (Exception $e) {
            // Log::error('Failed to initialize FuelSheetUtil: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Update regular fuel price by shift and date
     */
    public static function updateFuelPrice(string $shift, float $price, string $date, string $addedRegular = "0" ): bool
    {
        try {
			$price = $price / 100;
            // Log::info("Called updateFuelPrice with shift={$shift}, price={$price}, date={$date}");

            self::init();

            $monthTab = Carbon::parse($date)->format('F'); // e.g., January
            Log::info("Detected month tab: {$monthTab}");

            $row = self::findRowByDate($monthTab, $date);
            if (!$row) {
                // Log::error("Date {$date} not found in sheet {$monthTab}");
                return false;
            }
            // Log::info("Found row {$row} for date {$date}");

            // Determine column based on shift
            $column = null;
            if (strtolower($shift) === 'morning') {
                $column = 'D';
            } elseif (strtolower($shift) === 'evening') {
                $column = 'E';
            } else {
                // Log::error("Unknown shift: {$shift}");
                return false;
            }
            Log::info("Shift {$shift} corresponds to column {$column}");

            $range = "{$monthTab}!{$column}{$row}";
            // Log::info("Updating cell {$range} with value {$price}");

            $success = self::updateCell($range, number_format($price, 3));

			if (strtolower($shift) === 'evening') {
				$column = 'B';
				$range = "{$monthTab}!{$column}{$row}";
				$price = $price / 1.05;
				$success = self::updateCell($range, $price);
			}

			if ($addedRegular !== "0") {
				$addedRange = "{$monthTab}!H{$row}"; // Assuming column H is for added regular
				// Log::info("Updating cell {$addedRange} with value {$addedRegular}");
				self::updateCell($addedRange, $addedRegular);
			}

            if ($success) {
                // Log::info("Successfully updated fuel price for {$shift} shift on {$date}");
            }

            return $success;

        } catch (Exception $e) {
            // Log::error('Failed to updateFuelPrice: ' . $e->getMessage());
            return false;
        }
    }
	/**
	 * Update premium fuel price by shift and date
	 */
	public static function updateFuelVolumeAndSales(string $date, string $volumne, string $amount): bool
    {
        try {
            self::init();

            $monthTab = Carbon::parse($date)->format('F'); // e.g., January

            $row = self::findRowByDate($monthTab, $date);
            if (!$row) {
                // Log::error("Date {$date} not found in sheet {$monthTab}");
                return false;
            }
            // Log::info("Found row {$row} for date {$date}");

			$column = 'F'; // Volume column
            $range = "{$monthTab}!{$column}{$row}";
            // Log::info("Updating cell {$range} with value {$volumne}");
            $success = self::updateCell($range, $volumne);

			$column = 'G'; // Volume column
            $range = "{$monthTab}!{$column}{$row}";
            // Log::info("Updating cell {$range} with value {$amount}");
            $success = self::updateCell($range, $amount);

            if ($success) {
                // Log::info("Successfully updated fuel price for {$shift} shift on {$date}");
            }

            return $success;

        } catch (Exception $e) {
            // Log::error('Failed to updateFuelPrice: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update a single cell
     */
    private static function updateCell(string $range, $value): bool
    {
        try {
            $body = new ValueRange([
                'values' => [[$value]]
            ]);

            self::$service->spreadsheets_values->update(
                self::$spreadsheetId,
                $range,
                $body,
                ['valueInputOption' => 'RAW']
            );

            // Log::info("Updated cell {$range} with value {$value}");
            return true;

        } catch (Exception $e) {
            // Log::error("Failed to update cell {$range}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Find row number in Column A by date
     */
    private static function findRowByDate(string $sheetName, string $date): ?int
    {
        try {
            // Log::info("Searching for date {$date} in sheet {$sheetName} column A");

            $range = "{$sheetName}!A:A";
            $response = self::$service->spreadsheets_values->get(self::$spreadsheetId, $range);
            $values = $response->getValues();

            if (!$values) {
                // Log::warning("No data found in column A of sheet {$sheetName}");
                return null;
            }

            $target = Carbon::parse($date)->format('Y-m-d');

            foreach ($values as $index => $row) {
                if (!isset($row[0])) continue;

                try {
                    $sheetDate = Carbon::parse($row[0])->format('Y-m-d');
                    if ($sheetDate === $target) {
                        // Log::info("Matched date {$date} at row " . ($index + 1));
                        return $index + 1; // Sheets rows are 1-indexed
                    }
                } catch (Exception $e) {
                    continue;
                }
            }

            // Log::warning("Date {$date} not found in sheet {$sheetName}");
            return null;

        } catch (Exception $e) {
            // Log::error("Failed to findRowByDate in {$sheetName}: " . $e->getMessage());
            return null;
        }
    }
}