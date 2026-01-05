<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\DailySale;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use App\Utils\TimezoneUtil;

class ImportDailySalesFromCsv extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:daily-sales {file : Path to the CSV file} {--skip-header : Skip the first row if it contains headers}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import daily sales data from a CSV file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = $this->argument('file');
        $skipHeader = $this->option('skip-header');

        // Check if file exists
        if (!file_exists($filePath)) {
            $this->error("File not found: {$filePath}");
            return 1;
        }

        $this->info("Starting import from: {$filePath}");
        
        $successCount = 0;
        $errorCount = 0;
        $skippedCount = 0;

        // Read CSV file
        $handle = fopen($filePath, 'r');
        if (!$handle) {
            $this->error("Could not open file: {$filePath}");
            return 1;
        }

        $rowNumber = 0;
        while (($data = fgetcsv($handle)) !== false) {
            $rowNumber++;
            
            // Skip header row if specified
            if ($skipHeader && $rowNumber === 1) {
                $this->info("Skipping header row");
                continue;
            }

            // Expected CSV format:
            // Date, Fuel Sale, Store Sale, GST, Card, Cash, Coupon, Delivery, Reported Total, Notes
            if (count($data) < 9) {
                $this->warn("Row {$rowNumber}: Insufficient data columns. Skipping.");
                $skippedCount++;
                continue;
            }

            try {
                $saleData = [
                    'date' => $this->parseDate($data[0]),
                    'fuel_sale' => $this->parseNumber($data[1]),
                    'store_sale' => $this->parseNumber($data[2]),
                    'gst' => $this->parseNumber($data[3]),
                    'card' => $this->parseNumber($data[4]),
                    'cash' => $this->parseNumber($data[5]),
                    'coupon' => $this->parseNumber($data[6]),
                    'delivery' => $this->parseNumber($data[7]),
                    'reported_total' => $this->parseNumber($data[8]),
                    'notes' => isset($data[9]) ? trim($data[9]) : null,
                ];

                // Validate data
                $validator = Validator::make($saleData, [
                    'date' => 'required|date|unique:daily_sales,date',
                    'fuel_sale' => 'required|numeric|min:0',
                    'store_sale' => 'required|numeric|min:0',
                    'gst' => 'required|numeric|min:0',
                    'card' => 'required|numeric|min:0',
                    'cash' => 'required|numeric',
                    'coupon' => 'required|numeric|min:0',
                    'delivery' => 'required|numeric|min:0',
                    'reported_total' => 'required|numeric|min:0',
                    'notes' => 'nullable|string',
                ]);

                if ($validator->fails()) {
                    $this->warn("Row {$rowNumber}: Validation failed - " . $validator->errors()->first());
                    $errorCount++;
                    continue;
                }

                // Create the record
                DailySale::create($saleData);
                $successCount++;
                
                $this->info("Row {$rowNumber}: Successfully imported for date {$saleData['date']}");

            } catch (\Exception $e) {
                $this->error("Row {$rowNumber}: Error - " . $e->getMessage());
                $errorCount++;
            }
        }

        fclose($handle);

        // Summary
        $this->newLine();
        $this->info("Import completed!");
        $this->info("✅ Successfully imported: {$successCount} records");
        $this->info("❌ Errors: {$errorCount} records");
        $this->info("⏭️ Skipped: {$skippedCount} records");

        return 0;
    }

    /**
     * Parse date from various formats
     */
    private function parseDate($dateString)
    {
        $dateString = trim($dateString);
        
        // Try different date formats
        $formats = [
            'Y-m-d',      // 2024-01-15
            'd/m/Y',      // 15/01/2024
            'm/d/Y',      // 01/15/2024
            'd-m-Y',      // 15-01-2024
            'm-d-Y',      // 01-15-2024
            'Y/m/d',      // 2024/01/15
        ];

        foreach ($formats as $format) {
            try {
                $date = TimezoneUtil::createFromFormat($format, $dateString);
                return $date->format('Y-m-d');
            } catch (\Exception $e) {
                continue;
            }
        }

        throw new \Exception("Unable to parse date: {$dateString}");
    }

    /**
     * Parse number from string
     */
    private function parseNumber($numberString)
    {
        $numberString = trim($numberString);
        
        // Remove currency symbols and commas
        $numberString = preg_replace('/[^\d.-]/', '', $numberString);
        
        $number = floatval($numberString);
        
        if ($number < 0) {
            throw new \Exception("Negative numbers are not allowed");
        }
        
        return $number;
    }
}
