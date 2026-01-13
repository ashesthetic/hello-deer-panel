<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Google Sheets Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for Google Sheets API integration
    |
    */

    'spreadsheet_id' => env('GOOGLE_SHEETS_SPREADSHEET_ID'),
    
    'service_account_path' => storage_path('app/google-service-account.json'),
    
    /*
    |--------------------------------------------------------------------------
    | Cell Mappings
    |--------------------------------------------------------------------------
    |
    | Define which cells in your Google Sheet correspond to different data points
    | Modify these based on your actual Google Sheet structure
    |
    */
    'cells' => [
        // Current fuel prices
        'regular_price' => env('GOOGLE_SHEETS_REGULAR_PRICE_CELL', 'Sheet1!B2'),
        'premium_price' => env('GOOGLE_SHEETS_PREMIUM_PRICE_CELL', 'Sheet1!C2'),
        'diesel_price' => env('GOOGLE_SHEETS_DIESEL_PRICE_CELL', 'Sheet1!D2'),
        
        // Last updated timestamp
        'last_updated' => env('GOOGLE_SHEETS_LAST_UPDATED_CELL', 'Sheet1!E2'),
        
        // Optional: Historical data cells (if you want to append historical data)
        'history_range' => env('GOOGLE_SHEETS_HISTORY_RANGE', 'History!A:E'),
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Update Settings
    |--------------------------------------------------------------------------
    */
    'enable_updates' => env('GOOGLE_SHEETS_ENABLE_UPDATES', true),
    
    'retry_attempts' => env('GOOGLE_SHEETS_RETRY_ATTEMPTS', 3),
    
    'retry_delay' => env('GOOGLE_SHEETS_RETRY_DELAY', 5), // seconds
    
    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    */
    'log_updates' => env('GOOGLE_SHEETS_LOG_UPDATES', true),
];