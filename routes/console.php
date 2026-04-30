<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Fetch GasBuddy prices every 30 minutes and check for price alerts
Schedule::command('gasbuddy:fetch')->everyThirtyMinutes();

// Import NAXML POSJournal files for today every 10 minutes
Schedule::command('naxml:import')->everyTenMinutes();
