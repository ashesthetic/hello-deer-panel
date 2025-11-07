<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Profit Margins Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the profit margin percentages for different product categories.
    | These values are used to calculate approximate daily profit.
    |
    */

    'fuel_percentage' => env('FUEL_PROFIT_PERCENTAGE', 4),
    'tobacco_25_percentage' => env('TOBACCO_25_PROFIT_PERCENTAGE', 8),
    'tobacco_20_percentage' => env('TOBACCO_20_PROFIT_PERCENTAGE', 8),
    'lottery_percentage' => env('LOTTERY_PROFIT_PERCENTAGE', 2),
    'prepay_percentage' => env('PREPAY_PROFIT_PERCENTAGE', 1),
    'store_sale_percentage' => env('STORE_SALE_PROFIT_PERCENTAGE', 50),
    
    /*
    |--------------------------------------------------------------------------
    | ATM Surcharge Rate
    |--------------------------------------------------------------------------
    |
    | This is the surcharge fee charged per ATM transaction.
    |
    */
    'atm_surcharge_rate' => env('ATM_SURCHARGE_RATE', 2.5),
]; 