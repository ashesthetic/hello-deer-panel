<?php

namespace App\Console\Commands;

use App\Models\NaxmlImport;
use App\Services\NaxmlImporterService;
use Illuminate\Console\Command;

class ScanPosReceiveFolder extends Command
{
    protected $signature = 'pos:scan {--batch= : Maximum number of NAXML files to process per run (overrides POS_SCAN_BATCH_SIZE)}';
    protected $description = 'Scan the POS receive folder, sweep companion files to backup, and import new NAXML POSJournal files';

    public function handle(NaxmlImporterService $importer): int
    {
        $receiveDir = rtrim($this->resolvePath(env('POS_RECEIVE_PATH', 'pos/data/trx/receive')), '/');
        $backupBase = rtrim($this->resolvePath(env('POS_BACKUP_PATH', 'pos/data/trx/backup')), '/');
        $batch      = (int) ($this->option('batch') ?? env('POS_SCAN_BATCH_SIZE', 20));

        if (!is_dir($receiveDir)) {
            $this->error("Receive directory not found: {$receiveDir}");
            return Command::FAILURE;
        }

        $swept    = 0;
        $processed = 0;
        $failed   = 0;

        // STEP 1 — sweep every non-NAXML file to backup immediately; they need no processing
        foreach (scandir($receiveDir) as $filename) {
            if ($filename === '.' || $filename === '..') continue;
            $filepath = $receiveDir . '/' . $filename;
            if (!is_file($filepath) || $this->isNaxmlJournal($filename)) continue;

            $date = date('Y-m-d', filemtime($filepath));
            if ($this->moveToBackup($filepath, $filename, $backupBase, $date)) {
                $swept++;
            }
        }

        // STEP 2 — collect NAXML files present in receive (presence = not yet processed)
        $naxmlFiles = glob($receiveDir . '/NAXML-POSJournal*.XML') ?: [];

        if (empty($naxmlFiles)) {
            $this->info("Swept {$swept} companion file(s). No NAXML files pending.");
            return Command::SUCCESS;
        }

        $pending   = array_slice($naxmlFiles, 0, $batch);
        $deferred  = count($naxmlFiles) - count($pending);

        // STEP 3 — import each NAXML file; move on success, leave on failure
        foreach ($pending as $filepath) {
            $import = $importer->importFile($filepath);

            if ($import->status === 'completed') {
                $date = $import->business_date
                    ? $import->business_date->toDateString()
                    : date('Y-m-d');
                $dest = $this->moveToBackup($filepath, basename($filepath), $backupBase, $date);
                if ($dest) {
                    // Keep filepath column in sync with file's new location
                    $import->update(['filepath' => $dest]);
                }
                $processed++;
            } else {
                $this->warn('Failed: ' . basename($filepath) . ' — ' . $import->error_message);
                $failed++;
            }
        }

        $deferredNote = $deferred > 0 ? ", {$deferred} deferred to next run" : '';
        $this->info("Swept {$swept} companion file(s). NAXML: {$processed} imported, {$failed} failed{$deferredNote}.");

        if ($failed > 0) {
            $this->line('Check <comment>naxml_imports</comment> table for error details.');
        }

        return Command::SUCCESS;
    }

    /** Absolute paths are used as-is; relative paths are resolved from the Laravel project root. */
    private function resolvePath(string $path): string
    {
        return str_starts_with($path, '/') ? $path : base_path($path);
    }

    private function isNaxmlJournal(string $filename): bool
    {
        return fnmatch('NAXML-POSJournal*.XML', $filename);
    }

    /**
     * Move a file to pos/data/trx/backup/{date}/{filename}.
     * Returns the destination path on success, null on failure.
     */
    private function moveToBackup(string $src, string $filename, string $backupBase, string $date): ?string
    {
        $destDir = $backupBase . '/' . $date;

        if (!is_dir($destDir) && !mkdir($destDir, 0755, true)) {
            $this->warn("Cannot create backup directory: {$destDir}");
            return null;
        }

        $dest = $destDir . '/' . $filename;

        if (!rename($src, $dest)) {
            $this->warn("Cannot move {$filename} to backup.");
            return null;
        }

        return $dest;
    }
}
