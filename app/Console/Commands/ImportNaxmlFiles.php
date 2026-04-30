<?php

namespace App\Console\Commands;

use App\Services\NaxmlImporterService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ImportNaxmlFiles extends Command
{
    protected $signature = 'naxml:import {date? : Date in YYYY-MM-DD format, defaults to today} {--force : Re-process files that were already imported}';
    protected $description = 'Import NAXML POSJournal files for a given date into the database';

    public function handle(NaxmlImporterService $importer): int
    {
        $date = $this->argument('date') ?? Carbon::today()->toDateString();

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $this->error("Invalid date format: {$date}. Expected YYYY-MM-DD.");
            return Command::FAILURE;
        }

        $force = $this->option('force');
        $this->info("Importing NAXML files for {$date}" . ($force ? ' (force re-import)' : '') . '...');

        $results = $importer->importForDate($date, $force);

        if (isset($results['error'])) {
            $this->error($results['error']);
            return Command::FAILURE;
        }

        $this->table(
            ['Total', 'Processed', 'Skipped', 'Failed'],
            [[$results['total'], $results['processed'], $results['skipped'], $results['failed']]]
        );

        if ($results['failed'] > 0) {
            $this->warn("{$results['failed']} file(s) failed to import. Check naxml_imports table for error details.");
        }

        return Command::SUCCESS;
    }
}
