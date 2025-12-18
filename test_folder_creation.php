<?php

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';

use App\Services\GoogleDriveService;
use Carbon\Carbon;

// Test folder creation functionality
try {
    $driveService = new GoogleDriveService();
    
    echo "Testing Google Drive folder creation...\n";
    
    // Test with current date
    $currentDate = Carbon::now();
    echo "Testing with date: " . $currentDate->format('Y-m-d') . "\n";
    
    $folderId = $driveService->getOrCreateYearMonthFolder($currentDate->format('Y-m-d'));
    
    if ($folderId) {
        echo "✓ Successfully created/found folder with ID: " . $folderId . "\n";
    } else {
        echo "✗ Failed to create/find folder\n";
    }
    
    // Test with specific date
    $testDate = '2024-12-15';
    echo "\nTesting with specific date: " . $testDate . "\n";
    
    $folderId2 = $driveService->getOrCreateYearMonthFolder($testDate);
    
    if ($folderId2) {
        echo "✓ Successfully created/found folder with ID: " . $folderId2 . "\n";
    } else {
        echo "✗ Failed to create/find folder\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
