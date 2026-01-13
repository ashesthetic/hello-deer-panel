<?php

namespace App\Jobs;

use App\Models\FuelVolume;
use App\Services\GoogleSheetsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Exception;

class UpdateGoogleSheetJob implements ShouldQueue
{
    use Queueable;

    public $tries = 3;
    public $backoff = [5, 10, 30]; // Retry delays in seconds

    private $fuelVolume;
    private $action; // 'created' or 'updated'

    /**
     * Create a new job instance.
     */
    public function __construct(FuelVolume $fuelVolume, string $action = 'created')
    {
        $this->fuelVolume = $fuelVolume;
        $this->action = $action;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if (!config('google.enable_updates', true)) {
            Log::info('Google Sheets updates are disabled');
            return;
        }

        try {
            $sheetsService = new GoogleSheetsService();
            
            // Prepare fuel prices for update
            $prices = [];
            
            if ($this->fuelVolume->regular_price !== null) {
                $prices['regular'] = $this->fuelVolume->regular_price;
            }
            
            if ($this->fuelVolume->premium_price !== null) {
                $prices['premium'] = $this->fuelVolume->premium_price;
            }
            
            if ($this->fuelVolume->diesel_price !== null) {
                $prices['diesel'] = $this->fuelVolume->diesel_price;
            }

            if (empty($prices)) {
                Log::info('No fuel prices to update in Google Sheets for FuelVolume ID: ' . $this->fuelVolume->id);
                return;
            }

            // Update Google Sheets with the new prices
            $result = $sheetsService->updateMultipleFuelPrices($prices, $this->fuelVolume->date);
            
            if ($result) {
                Log::info("Successfully updated Google Sheets for FuelVolume ID: {$this->fuelVolume->id} ({$this->action})", [
                    'fuel_volume_id' => $this->fuelVolume->id,
                    'date' => $this->fuelVolume->date,
                    'shift' => $this->fuelVolume->shift,
                    'prices' => $prices,
                    'action' => $this->action
                ]);
            } else {
                throw new Exception('Failed to update Google Sheets - no exception thrown but result was false');
            }

        } catch (Exception $e) {
            Log::error("Failed to update Google Sheets for FuelVolume ID: {$this->fuelVolume->id}", [
                'error' => $e->getMessage(),
                'fuel_volume_id' => $this->fuelVolume->id,
                'action' => $this->action,
                'attempt' => $this->attempts()
            ]);
            
            // Re-throw the exception to trigger retry logic
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(?Throwable $exception): void
    {
        Log::error("Google Sheets update job failed permanently for FuelVolume ID: {$this->fuelVolume->id}", [
            'error' => $exception?->getMessage(),
            'fuel_volume_id' => $this->fuelVolume->id,
            'action' => $this->action,
            'max_attempts_reached' => true
        ]);
    }
}
