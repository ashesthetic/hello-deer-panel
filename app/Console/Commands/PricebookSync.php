<?php

namespace App\Console\Commands;

use App\Services\Pricebook\PricebookSyncOrchestrator;
use Illuminate\Console\Command;

class PricebookSync extends Command
{
    protected $signature = 'pricebook:sync {--force-full : Force a full re-sync (clears the stored revision and re-bootstraps from /snapshot)}';
    protected $description = 'Pull changes from the Pricebook Sync API and apply them to the local mirror tables';

    public function handle(PricebookSyncOrchestrator $orchestrator): int
    {
        $result = $orchestrator->run((bool) $this->option('force-full'));

        $this->table(
            array_keys($result),
            [array_map(fn ($value) => is_array($value) ? json_encode($value) : $value, $result)]
        );

        return in_array($result['outcome'], ['unauthenticated', 'error'], true)
            ? Command::FAILURE
            : Command::SUCCESS;
    }
}
