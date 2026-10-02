<?php

namespace App\Console\Commands;

use App\Models\PricebookSyncState;
use App\Services\Pricebook\PricebookSchema;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PricebookSyncStatus extends Command
{
    protected $signature = 'pricebook:status';
    protected $description = 'Show the current pricebook sync state and local mirror table row counts';

    public function handle(): int
    {
        $state = PricebookSyncState::current();

        $this->table(
            ['last_revision', 'head_etag', 'running_since', 'last_success_at', 'last_error', 'last_error_at'],
            [[
                $state->last_revision,
                $state->head_etag,
                $state->running_since,
                $state->last_success_at,
                $state->last_error,
                $state->last_error_at,
            ]]
        );

        $counts = collect(PricebookSchema::ALL_TABLES)
            ->map(fn ($table) => [$table, DB::table($table)->count()]);

        $this->table(['table', 'rows'], $counts->toArray());

        return Command::SUCCESS;
    }
}
