<?php

namespace App\Console\Commands;

use App\Services\GoogleSheetsService;
use Illuminate\Console\Command;
use Exception;

class TestGoogleSheetsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'google-sheets:test 
                           {--connection : Test connection only}
                           {--update : Test updating fuel prices}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Google Sheets integration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Google Sheets Integration...');
        
        try {
            $sheetsService = new GoogleSheetsService();
            
            if ($this->option('connection')) {
                return $this->testConnection($sheetsService);
            }
            
            if ($this->option('update')) {
                return $this->testUpdate($sheetsService);
            }
            
            // Default: run both tests
            $this->testConnection($sheetsService);
            $this->testUpdate($sheetsService);
            
        } catch (Exception $e) {
            $this->error('Failed to initialize Google Sheets service: ' . $e->getMessage());
            return 1;
        }
        
        return 0;
    }
    
    private function testConnection(GoogleSheetsService $sheetsService)
    {
        $this->info('Testing connection to Google Sheets...');
        
        if ($sheetsService->testConnection()) {
            $this->info('✅ Connection successful!');
        } else {
            $this->error('❌ Connection failed!');
            return 1;
        }
    }
    
    private function testUpdate(GoogleSheetsService $sheetsService)
    {
        $this->info('Testing fuel price updates...');
        
        // Test data
        $testPrices = [
            'regular' => 1.234,
            'premium' => 1.456,
            'diesel' => 1.678
        ];
        
        $this->info('Updating test fuel prices: ' . json_encode($testPrices));
        
        if ($sheetsService->updateMultipleFuelPrices($testPrices)) {
            $this->info('✅ Test update successful!');
            
            // Show current configuration
            $this->info('Current cell mappings:');
            $this->line('  Regular: ' . config('google.cells.regular_price'));
            $this->line('  Premium: ' . config('google.cells.premium_price'));
            $this->line('  Diesel: ' . config('google.cells.diesel_price'));
            $this->line('  Last Updated: ' . config('google.cells.last_updated'));
            
        } else {
            $this->error('❌ Test update failed!');
            return 1;
        }
    }
}
