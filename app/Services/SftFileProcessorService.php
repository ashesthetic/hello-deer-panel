<?php

namespace App\Services;

use App\Models\FileImport;
use Illuminate\Support\Facades\Storage;

class SftFileProcessorService
{
    /**
     * Process all SFT files for a given date and return aggregated data
     */
    public function processSftFiles(string $importDate): array
    {
        // Ensure the date is in Y-m-d format
        $formattedDate = \Carbon\Carbon::parse($importDate)->format('Y-m-d');
        
        // Get all unprocessed SFT files for the given date
        $sftFiles = FileImport::whereDate('import_date', $formattedDate)
            ->where(function ($query) {
                $query->where('original_name', 'like', '%.sft')
                    ->orWhere('original_name', 'like', '%.SFT');
            })
            ->get();

        if ($sftFiles->isEmpty()) {
            return [
                'success' => false,
                'message' => "No SFT files found for the date: {$formattedDate}",
                'data' => null
            ];
        }

        $aggregatedData = [
            'total_sales' => 0,
            'fuel_sales' => 0,
            'item_sales' => 0,
            'gst' => 0,
            'penny_rounding' => 0,
            'total_pos' => 0,
            'canadian_cash' => 0,
            'safedrops_count' => 0,
            'safedrops_amount' => 0,
            'cash_on_hand' => 0,
            'fuel_tax_gst' => 0,
            'payouts' => 0,
            'loyalty_discounts' => 0,
            // POS Transaction Details
            'pos_visa' => 0,
            'pos_mastercard' => 0,
            'pos_amex' => 0,
            'pos_commercial' => 0,
            'pos_up_credit' => 0,
            'pos_discover' => 0,
            'pos_interac_debit' => 0,
            'pos_debit_transaction_count' => 0,
            // AFD Transaction Details
            'afd_visa' => 0,
            'afd_mastercard' => 0,
            'afd_amex' => 0,
            'afd_commercial' => 0,
            'afd_up_credit' => 0,
            'afd_discover' => 0,
            'afd_interac_debit' => 0,
            'afd_debit_transaction_count' => 0,
            // Department Totals
            'tobacco_25' => 0,
            'tobacco_20' => 0,
            'lottery_total' => 0,
            'prepay_total' => 0,
            // Loyalty Discounts
            'journey_discount' => 0,
            'aeroplan_discount' => 0,
            'files_processed' => 0,
            'files_with_errors' => 0,
            'processed_files' => [],
            'errors' => []
        ];

        foreach ($sftFiles as $file) {
            try {
                $fileData = $this->parseSftFile($file);
                
                if ($fileData['success']) {
                    $aggregatedData['total_sales'] += $fileData['data']['total_sales'];
                    $aggregatedData['fuel_sales'] += $fileData['data']['fuel_sales'];
                    $aggregatedData['item_sales'] += $fileData['data']['item_sales'];
                    $aggregatedData['gst'] += $fileData['data']['gst'];
                    $aggregatedData['penny_rounding'] += $fileData['data']['penny_rounding'];
                    $aggregatedData['total_pos'] += $fileData['data']['total_pos'];
                    $aggregatedData['canadian_cash'] += $fileData['data']['canadian_cash'];
                    $aggregatedData['safedrops_count'] += $fileData['data']['safedrops_count'];
                    $aggregatedData['safedrops_amount'] += $fileData['data']['safedrops_amount'];
                    $aggregatedData['cash_on_hand'] += $fileData['data']['cash_on_hand'];
                    $aggregatedData['fuel_tax_gst'] += $fileData['data']['fuel_tax_gst'];
                    $aggregatedData['payouts'] += $fileData['data']['payouts'];
                    $aggregatedData['loyalty_discounts'] += $fileData['data']['loyalty_discounts'];
                    // POS Transaction Details
                    $aggregatedData['pos_visa'] += $fileData['data']['pos_visa'];
                    $aggregatedData['pos_mastercard'] += $fileData['data']['pos_mastercard'];
                    $aggregatedData['pos_amex'] += $fileData['data']['pos_amex'];
                    $aggregatedData['pos_commercial'] += $fileData['data']['pos_commercial'];
                    $aggregatedData['pos_up_credit'] += $fileData['data']['pos_up_credit'];
                    $aggregatedData['pos_discover'] += $fileData['data']['pos_discover'];
                    $aggregatedData['pos_interac_debit'] += $fileData['data']['pos_interac_debit'];
                    $aggregatedData['pos_debit_transaction_count'] += $fileData['data']['pos_debit_transaction_count'];
                    // AFD Transaction Details
                    $aggregatedData['afd_visa'] += $fileData['data']['afd_visa'];
                    $aggregatedData['afd_mastercard'] += $fileData['data']['afd_mastercard'];
                    $aggregatedData['afd_amex'] += $fileData['data']['afd_amex'];
                    $aggregatedData['afd_commercial'] += $fileData['data']['afd_commercial'];
                    $aggregatedData['afd_up_credit'] += $fileData['data']['afd_up_credit'];
                    $aggregatedData['afd_discover'] += $fileData['data']['afd_discover'];
                    $aggregatedData['afd_interac_debit'] += $fileData['data']['afd_interac_debit'];
                    $aggregatedData['afd_debit_transaction_count'] += $fileData['data']['afd_debit_transaction_count'];
                    // Department Totals
                    $aggregatedData['tobacco_25'] += $fileData['data']['tobacco_25'];
                    $aggregatedData['tobacco_20'] += $fileData['data']['tobacco_20'];
                    $aggregatedData['lottery_total'] += $fileData['data']['lottery_total'];
                    $aggregatedData['prepay_total'] += $fileData['data']['prepay_total'];
                    // Loyalty Discounts
                    $aggregatedData['journey_discount'] += $fileData['data']['journey_discount'];
                    $aggregatedData['aeroplan_discount'] += $fileData['data']['aeroplan_discount'];
                    $aggregatedData['files_processed']++;
                    
                    $aggregatedData['processed_files'][] = [
                        'file_name' => $file->original_name,
                        'total_sales' => $fileData['data']['total_sales'],
                        'fuel_sales' => $fileData['data']['fuel_sales'],
                        'item_sales' => $fileData['data']['item_sales'],
                        'gst' => $fileData['data']['gst'],
                        'penny_rounding' => $fileData['data']['penny_rounding'],
                        'total_pos' => $fileData['data']['total_pos'],
                        'canadian_cash' => $fileData['data']['canadian_cash'],
                        'safedrops_count' => $fileData['data']['safedrops_count'],
                        'safedrops_amount' => $fileData['data']['safedrops_amount'],
                        'cash_on_hand' => $fileData['data']['cash_on_hand'],
                        'fuel_tax_gst' => $fileData['data']['fuel_tax_gst'],
                        'payouts' => $fileData['data']['payouts'],
                        'loyalty_discounts' => $fileData['data']['loyalty_discounts'],
                        // POS Transaction Details
                        'pos_visa' => $fileData['data']['pos_visa'],
                        'pos_mastercard' => $fileData['data']['pos_mastercard'],
                        'pos_amex' => $fileData['data']['pos_amex'],
                        'pos_commercial' => $fileData['data']['pos_commercial'],
                        'pos_up_credit' => $fileData['data']['pos_up_credit'],
                        'pos_discover' => $fileData['data']['pos_discover'],
                        'pos_interac_debit' => $fileData['data']['pos_interac_debit'],
                        'pos_debit_transaction_count' => $fileData['data']['pos_debit_transaction_count'],
                        // AFD Transaction Details
                        'afd_visa' => $fileData['data']['afd_visa'],
                        'afd_mastercard' => $fileData['data']['afd_mastercard'],
                        'afd_amex' => $fileData['data']['afd_amex'],
                        'afd_commercial' => $fileData['data']['afd_commercial'],
                        'afd_up_credit' => $fileData['data']['afd_up_credit'],
                        'afd_discover' => $fileData['data']['afd_discover'],
                        'afd_interac_debit' => $fileData['data']['afd_interac_debit'],
                        'afd_debit_transaction_count' => $fileData['data']['afd_debit_transaction_count'],
                        // Department Totals
                        'tobacco_25' => $fileData['data']['tobacco_25'],
                        'tobacco_20' => $fileData['data']['tobacco_20'],
                        'lottery_total' => $fileData['data']['lottery_total'],
                        'prepay_total' => $fileData['data']['prepay_total'],
                        // Loyalty Discounts
                        'journey_discount' => $fileData['data']['journey_discount'],
                        'aeroplan_discount' => $fileData['data']['aeroplan_discount']
                    ];
                    
                    // Mark file as processed
                    $file->markAsProcessed();
                } else {
                    $aggregatedData['files_with_errors']++;
                    $aggregatedData['errors'][] = [
                        'file_name' => $file->original_name,
                        'error' => $fileData['message']
                    ];
                }
            } catch (\Exception $e) {
                $aggregatedData['files_with_errors']++;
                $aggregatedData['errors'][] = [
                    'file_name' => $file->original_name,
                    'error' => 'Exception: ' . $e->getMessage()
                ];
            }
        }

        return [
            'success' => true,
            'message' => "Processed {$aggregatedData['files_processed']} files successfully",
            'data' => $aggregatedData
        ];
    }

    /**
     * Parse individual SFT file and extract sales data
     */
    private function parseSftFile(FileImport $file): array
    {
        try {
            $filePath = $file->getFullPath();
            
            if (!file_exists($filePath)) {
                return [
                    'success' => false,
                    'message' => 'File not found: ' . $file->original_name
                ];
            }

            $content = file_get_contents($filePath);
            if ($content === false) {
                return [
                    'success' => false,
                    'message' => 'Could not read file: ' . $file->original_name
                ];
            }

            // Split content into lines
            $lines = explode("\n", $content);
            
            $salesData = [
                'total_sales' => 0,
                'fuel_sales' => 0,
                'item_sales' => 0,
                'gst' => 0,
                'penny_rounding' => 0,
                'total_pos' => 0,
                'canadian_cash' => 0,
                'safedrops_count' => 0,
                'safedrops_amount' => 0,
                'cash_on_hand' => 0,
                'fuel_tax_gst' => 0,
                'payouts' => 0,
                'loyalty_discounts' => 0,
                // POS Transaction Details
                'pos_visa' => 0,
                'pos_mastercard' => 0,
                'pos_amex' => 0,
                'pos_commercial' => 0,
                'pos_up_credit' => 0,
                'pos_discover' => 0,
                'pos_interac_debit' => 0,
                'pos_debit_transaction_count' => 0,
                // AFD Transaction Details
                'afd_visa' => 0,
                'afd_mastercard' => 0,
                'afd_amex' => 0,
                'afd_commercial' => 0,
                'afd_up_credit' => 0,
                'afd_discover' => 0,
                'afd_interac_debit' => 0,
                'afd_debit_transaction_count' => 0,
                // Department Totals
                'tobacco_25' => 0,
                'tobacco_20' => 0,
                'lottery_total' => 0,
                'prepay_total' => 0,
                // Loyalty Discounts
                'journey_discount' => 0,
                'aeroplan_discount' => 0
            ];

            // Parse lines to find sales data
            $currentSection = '';
            $currentDepartment = '';
            $currentLoyaltySection = '';
            foreach ($lines as $line) {
                $line = trim($line);
                
                // Track departments
                if (preg_match('/Department: \d+\s+(.*)/', $line, $matches)) {
                    $currentDepartment = trim($matches[1]);
                    continue;
                }
                
                // Track loyalty sections
                if (preg_match('/^\s*Aeroplan\s*$/', $line)) {
                    $currentLoyaltySection = 'aeroplan';
                    continue;
                } elseif (preg_match('/^\s*JOURNIE Rewards\s*$/', $line)) {
                    $currentLoyaltySection = 'journey';
                    continue;
                } elseif (preg_match('/^\s*Points earned\s*$/', $line)) {
                    // End of loyalty section
                    $currentLoyaltySection = '';
                }
                
                // Track which section we're in
                if (preg_match('/^\s*AFD CREDIT POS TOTALS\s*$/', $line)) {
                    $currentSection = 'afd_credit_totals';
                    continue;
                } elseif (preg_match('/^\s*AFD DEBIT POS TOTALS\s*$/', $line)) {
                    $currentSection = 'afd_debit_totals';
                    continue;
                } elseif (preg_match('/^\s*POS TOTALS\s*$/', $line)) {
                    $currentSection = 'pos_totals';
                    continue;
                } elseif (preg_match('/^\s*DEBIT TOTALS\s*$/', $line)) {
                    $currentSection = 'debit_totals';
                    continue;
                } elseif (preg_match('/^[A-Z].*[A-Z]$/', $line) && strlen($line) > 10) {
                    // New section header, reset current section
                    $currentSection = '';
                }
                
                // Look for Fuel sales
                if (preg_match('/Fuel sales\s+(\d+\.\d+)/', $line, $matches)) {
                    $salesData['fuel_sales'] = (float) $matches[1];
                }
                
                // Look for Item Sales
                if (preg_match('/Item Sales\s+(\d+\.\d+)/', $line, $matches)) {
                    $salesData['item_sales'] = (float) $matches[1];
                }
                
                // Look for Total Sales
                if (preg_match('/Total Sales\s+(\d+\.\d+)/', $line, $matches)) {
                    $salesData['total_sales'] = (float) $matches[1];
                }
                
                // Look for GST
                if (preg_match('/^\s*GST\s+(\d+\.\d+)/', $line, $matches)) {
                    $salesData['gst'] = (float) $matches[1];
                }
                
                // Look for Penny Rounding
                if (preg_match('/Penny Rounding\s+(\d+\.\d+)/', $line, $matches)) {
                    $salesData['penny_rounding'] = (float) $matches[1];
                }
                
                // Look for Total POS
                if (preg_match('/Total POS\s+(\d+\.\d+)/', $line, $matches)) {
                    $salesData['total_pos'] = (float) $matches[1];
                }
                
                // Look for Canadian Cash
                if (preg_match('/Canadian Cash\s+(\d+\.\d+)/', $line, $matches)) {
                    $salesData['canadian_cash'] = (float) $matches[1];
                }
                
                // Look for Safedrops (format: "Safedrops      2    178.35")
                if (preg_match('/Safedrops\s+(\d+)\s+(\d+\.\d+)/', $line, $matches)) {
                    $salesData['safedrops_count'] = (int) $matches[1];
                    $salesData['safedrops_amount'] = (float) $matches[2];
                }
                
                // Look for Cash On Hand
                if (preg_match('/Cash On Hand\s+(\d+\.\d+)/', $line, $matches)) {
                    $salesData['cash_on_hand'] = (float) $matches[1];
                }
                
                // Look for Fuel tax - GST
                if (preg_match('/Fuel tax - GST\s+\$\s*(\d+\.\d+)/', $line, $matches)) {
                    $salesData['fuel_tax_gst'] = (float) $matches[1];
                }
                
                // Look for Payouts
                if (preg_match('/Payouts\s+(\d+\.\d+)/', $line, $matches)) {
                    $salesData['payouts'] = (float) $matches[1];
                }
                
                // Look for Total loyalty discounts
                if (preg_match('/Total loyalty discounts\s+(\d+\.\d+)/', $line, $matches)) {
                    $salesData['loyalty_discounts'] += (float) $matches[1];
                }
                
                // Parse transaction details based on current section
                if ($currentSection === 'pos_totals') {
                    if (preg_match('/VISA\s+(\d+)\s+(\d+\.\d+)\s+\d+\s+\d+\.\d+/', $line, $matches)) {
                        $salesData['pos_visa'] = (float) $matches[2];
                    } elseif (preg_match('/MASTERCARD\s+(\d+)\s+(\d+\.\d+)\s+\d+\s+\d+\.\d+/', $line, $matches)) {
                        $salesData['pos_mastercard'] = (float) $matches[2];
                    } elseif (preg_match('/AMEX\s+(\d+)\s+(\d+\.\d+)\s+\d+\s+\d+\.\d+/', $line, $matches)) {
                        $salesData['pos_amex'] = (float) $matches[2];
                    } elseif (preg_match('/COMMERCIAL\s+(\d+)\s+(\d+\.\d+)\s+\d+\s+\d+\.\d+/', $line, $matches)) {
                        $salesData['pos_commercial'] = (float) $matches[2];
                    } elseif (preg_match('/UP CREDIT\s+(\d+)\s+(\d+\.\d+)\s+\d+\s+\d+\.\d+/', $line, $matches)) {
                        $salesData['pos_up_credit'] = (float) $matches[2];
                    } elseif (preg_match('/DISCOVER\s+(\d+)\s+(\d+\.\d+)\s+\d+\s+\d+\.\d+/', $line, $matches)) {
                        $salesData['pos_discover'] = (float) $matches[2];
                    }
                } elseif ($currentSection === 'debit_totals') {
                    if (preg_match('/INTERAC\s+(\d+)\s+(\d+\.\d+)\s+\d+\s+\d+\.\d+/', $line, $matches)) {
                        $salesData['pos_interac_debit'] = (float) $matches[2];
                        $salesData['pos_debit_transaction_count'] = (int) $matches[1];
                    }
                } elseif ($currentSection === 'afd_credit_totals') {
                    if (preg_match('/VISA\s+(\d+)\s+(\d+\.\d+)\s+\d+\s+\d+\.\d+/', $line, $matches)) {
                        $salesData['afd_visa'] = (float) $matches[2];
                    } elseif (preg_match('/MASTERCARD\s+(\d+)\s+(\d+\.\d+)\s+\d+\s+\d+\.\d+/', $line, $matches)) {
                        $salesData['afd_mastercard'] = (float) $matches[2];
                    } elseif (preg_match('/AMEX\s+(\d+)\s+(\d+\.\d+)\s+\d+\s+\d+\.\d+/', $line, $matches)) {
                        $salesData['afd_amex'] = (float) $matches[2];
                    } elseif (preg_match('/COMMERCIAL\s+(\d+)\s+(\d+\.\d+)\s+\d+\s+\d+\.\d+/', $line, $matches)) {
                        $salesData['afd_commercial'] = (float) $matches[2];
                    } elseif (preg_match('/UP CREDIT\s+(\d+)\s+(\d+\.\d+)\s+\d+\s+\d+\.\d+/', $line, $matches)) {
                        $salesData['afd_up_credit'] = (float) $matches[2];
                    } elseif (preg_match('/DISCOVER\s+(\d+)\s+(\d+\.\d+)\s+\d+\s+\d+\.\d+/', $line, $matches)) {
                        $salesData['afd_discover'] = (float) $matches[2];
                    }
                } elseif ($currentSection === 'afd_debit_totals') {
                    if (preg_match('/INTERAC\s+(\d+)\s+(\d+\.\d+)/', $line, $matches)) {
                        $salesData['afd_interac_debit'] = (float) $matches[2];
                        $salesData['afd_debit_transaction_count'] = (int) $matches[1];
                    }
                }
                
                // Parse department-based totals
                if (preg_match('/Grand Total\s+\d+\s+\d+\s+\$\s*(\d+\.\d+)/', $line, $matches)) {
                    $total = (float) $matches[1];
                    
                    if (stripos($currentDepartment, 'CIG SINGLE 25') !== false) {
                        $salesData['tobacco_25'] += $total;
                    } elseif (stripos($currentDepartment, 'CIG SINGLE 20') !== false) {
                        $salesData['tobacco_20'] += $total;
                    } elseif (stripos($currentDepartment, 'LOTTO') !== false || 
                              stripos($currentDepartment, 'SCRATCH LOTTERY') !== false) {
                        $salesData['lottery_total'] += $total;
                    } elseif (stripos($currentDepartment, 'PHONE CARDS') !== false) {
                        $salesData['prepay_total'] += $total;
                    }
                }
                
                // Extract loyalty discounts
                if ($currentLoyaltySection && preg_match('/Total\s+loyalty\s+discounts\s+([\d,]+\.?\d*)/', $line, $matches)) {
                    $value = floatval(str_replace(',', '', $matches[1]));
                    if ($currentLoyaltySection === 'journey') {
                        $salesData['journey_discount'] = $value;
                    } elseif ($currentLoyaltySection === 'aeroplan') {
                        $salesData['aeroplan_discount'] = $value;
                    }
                }
            }

            // Validate that we found some data
            if ($salesData['total_sales'] == 0 && $salesData['fuel_sales'] == 0 && $salesData['item_sales'] == 0) {
                return [
                    'success' => false,
                    'message' => 'No sales data found in file: ' . $file->original_name
                ];
            }

            return [
                'success' => true,
                'data' => $salesData
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error parsing file: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get list of available import dates with SFT files
     */
    public function getAvailableImportDates(): array
    {
        $dates = FileImport::where(function ($query) {
                $query->where('original_name', 'like', '%.sft')
                    ->orWhere('original_name', 'like', '%.SFT');
            })
            ->select('import_date')
            ->distinct()
            ->orderBy('import_date', 'desc')
            ->pluck('import_date')
            ->map(function ($date) {
                return $date->format('Y-m-d');
            })
            ->toArray();

        return $dates;
    }

    /**
     * Get SFT files for a specific date
     */
    public function getSftFilesForDate(string $importDate): array
    {
        // Ensure the date is in Y-m-d format
        $formattedDate = \Carbon\Carbon::parse($importDate)->format('Y-m-d');
        
        $files = FileImport::whereDate('import_date', $formattedDate)
            ->where(function ($query) {
                $query->where('original_name', 'like', '%.sft')
                    ->orWhere('original_name', 'like', '%.SFT');
            })
            ->select('id', 'original_name', 'file_size', 'processed', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();

        return $files;
    }
}
