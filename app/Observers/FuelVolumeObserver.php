<?php

namespace App\Observers;

use App\Models\FuelVolume;
use App\Jobs\UpdateGoogleSheetJob;
use Illuminate\Support\Facades\Log;

class FuelVolumeObserver
{
    /**
     * Handle the FuelVolume "created" event.
     */
    public function created(FuelVolume $fuelVolume): void
    {
        // Only trigger Google Sheets update if at least one price field is set
        if ($this->hasPriceData($fuelVolume)) {
            Log::info("FuelVolume created with price data - queuing Google Sheets update", [
                'fuel_volume_id' => $fuelVolume->id,
                'date' => $fuelVolume->date,
                'shift' => $fuelVolume->shift
            ]);
            
            UpdateGoogleSheetJob::dispatch($fuelVolume, 'created');
        }
    }

    /**
     * Handle the FuelVolume "updated" event.
     */
    public function updated(FuelVolume $fuelVolume): void
    {
        // Check if any price fields were changed
        if ($this->pricesChanged($fuelVolume)) {
            Log::info("FuelVolume price data updated - queuing Google Sheets update", [
                'fuel_volume_id' => $fuelVolume->id,
                'date' => $fuelVolume->date,
                'shift' => $fuelVolume->shift,
                'changed_fields' => $this->getChangedPriceFields($fuelVolume)
            ]);
            
            UpdateGoogleSheetJob::dispatch($fuelVolume, 'updated');
        }
    }

    /**
     * Handle the FuelVolume "deleted" event.
     */
    public function deleted(FuelVolume $fuelVolume): void
    {
        // Optionally handle deletion - maybe clear the cells or log the deletion
        Log::info("FuelVolume deleted", [
            'fuel_volume_id' => $fuelVolume->id,
            'date' => $fuelVolume->date,
            'shift' => $fuelVolume->shift
        ]);
    }

    /**
     * Check if the fuel volume has any price data
     */
    private function hasPriceData(FuelVolume $fuelVolume): bool
    {
        return $fuelVolume->regular_price !== null ||
               $fuelVolume->premium_price !== null ||
               $fuelVolume->diesel_price !== null;
    }

    /**
     * Check if any price fields were changed during update
     */
    private function pricesChanged(FuelVolume $fuelVolume): bool
    {
        return $fuelVolume->isDirty('regular_price') ||
               $fuelVolume->isDirty('premium_price') ||
               $fuelVolume->isDirty('diesel_price');
    }

    /**
     * Get the list of changed price fields
     */
    private function getChangedPriceFields(FuelVolume $fuelVolume): array
    {
        $changed = [];
        
        if ($fuelVolume->isDirty('regular_price')) {
            $changed['regular_price'] = [
                'old' => $fuelVolume->getOriginal('regular_price'),
                'new' => $fuelVolume->regular_price
            ];
        }
        
        if ($fuelVolume->isDirty('premium_price')) {
            $changed['premium_price'] = [
                'old' => $fuelVolume->getOriginal('premium_price'),
                'new' => $fuelVolume->premium_price
            ];
        }
        
        if ($fuelVolume->isDirty('diesel_price')) {
            $changed['diesel_price'] = [
                'old' => $fuelVolume->getOriginal('diesel_price'),
                'new' => $fuelVolume->diesel_price
            ];
        }
        
        return $changed;
    }
}
