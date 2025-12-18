<?php

require_once 'vendor/autoload.php';

use Carbon\Carbon;

echo "Testing date calculation...\n";
echo "Today: " . now()->format('Y-m-d (l)') . "\n";

// Test the old logic
$today = now();
$oldWeekStart = $today->copy()->startOfWeek();
echo "Old logic - Week start: " . $oldWeekStart->format('Y-m-d (l)') . "\n";

// Test the new logic
$newWeekStart = $today->copy()->startOfWeek(Carbon::MONDAY);
echo "New logic - Week start: " . $newWeekStart->format('Y-m-d (l)') . "\n";

// Test week ranges
for ($i = 0; $i < 3; $i++) {
    $weekStart = $newWeekStart->copy()->addWeeks($i);
    $weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);
    echo "Week " . ($i + 1) . ": " . $weekStart->format('M j') . " - " . $weekEnd->format('M j, Y') . "\n";
}

echo "\nTesting specific date (Aug 4, 2025):\n";
$testDate = Carbon::create(2025, 8, 4);
echo "Test date: " . $testDate->format('Y-m-d (l)') . "\n";

$weekStart = $testDate->copy()->startOfWeek(Carbon::MONDAY);
$weekEnd = $weekStart->copy()->endOfWeek(Carbon::SUNDAY);
echo "Week range: " . $weekStart->format('M j') . " - " . $weekEnd->format('M j, Y') . "\n";
