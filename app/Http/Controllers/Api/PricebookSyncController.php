<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PricebookSyncState;
use App\Services\Pricebook\PricebookSchema;
use App\Services\Pricebook\PricebookSyncOrchestrator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class PricebookSyncController extends Controller
{
    public function __construct(private PricebookSyncOrchestrator $orchestrator)
    {
    }

    public function status(): JsonResponse
    {
        $state = PricebookSyncState::current();

        $tableCounts = collect(PricebookSchema::ALL_TABLES)
            ->mapWithKeys(fn ($table) => [$table => DB::table($table)->count()]);

        return response()->json([
            'last_revision' => $state->last_revision,
            'last_snapshot_revision' => $state->last_snapshot_revision,
            'running_since' => $state->running_since,
            'last_success_at' => $state->last_success_at,
            'last_error' => $state->last_error,
            'last_error_at' => $state->last_error_at,
            'table_counts' => $tableCounts,
        ]);
    }

    public function syncNow(): JsonResponse
    {
        return response()->json($this->orchestrator->run(false));
    }

    public function forceFullResync(): JsonResponse
    {
        return response()->json($this->orchestrator->run(true));
    }
}
