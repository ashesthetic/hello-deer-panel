<?php

namespace Tests\Feature\Pricebook;

use App\Models\PricebookSyncState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class StatusCodeHandlingTest extends PricebookTestCase
{
    public function test_409_from_changes_triggers_a_full_bootstrap(): void
    {
        PricebookSyncState::current()->update(['last_revision' => 100]);

        $snapshot = $this->buildSnapshot([
            'pb_departments' => [
                ['department_number' => '000001', 'description' => 'GROCERY', 'shift_report_flag' => 1, 'sales_summary_report' => 1, 'created_at' => '2026-01-01 00:00:00', 'updated_at' => '2026-01-01 00:00:00', 'revision' => 500],
            ],
        ], revision: 500);

        Http::fake([
            $this->baseUrl . '/head' => Http::response(json_encode(['revision' => 500]), 200, ['ETag' => '"rev-500"']),
            $this->baseUrl . '/changes*' => Http::response(json_encode(['error' => 'snapshot_required', 'revision' => 500, 'reset_revision' => 500]), 409),
            $this->baseUrl . '/snapshot' => Http::response($snapshot['gz'], 200, [
                'X-Snapshot-Revision' => '500',
                'X-Snapshot-Checksum' => $snapshot['checksum'],
            ]),
        ]);

        $result = $this->orchestrator()->run();

        $this->assertSame('bootstrapped', $result['outcome']);
        $this->assertSame(500, PricebookSyncState::current()->last_revision);
        $this->assertSame(1, DB::table('pb_departments')->count());
    }

    public function test_503_leaves_data_untouched_and_can_retry_later(): void
    {
        PricebookSyncState::current()->update(['last_revision' => 100]);
        DB::table('pb_departments')->insert([
            'department_number' => '000001', 'description' => 'EXISTING', 'shift_report_flag' => 1, 'sales_summary_report' => 1,
        ]);

        // One combined sequence spans both calls: the first tick gets a 503,
        // the next tick (after the lock was released, not held) gets a clean 200.
        Http::fake([
            $this->baseUrl . '/head' => Http::sequence()
                ->push(
                    json_encode(['error' => 'pricebook_unavailable', 'reason' => 'pricebook_import_running']),
                    503,
                    ['Retry-After' => '30']
                )
                ->push(json_encode(['revision' => 100]), 200, ['ETag' => '"rev-100"']),
        ]);

        $result = $this->orchestrator()->run();

        $this->assertSame('retry_later', $result['outcome']);
        $this->assertSame(100, PricebookSyncState::current()->last_revision);
        $this->assertSame(1, DB::table('pb_departments')->count());
        $this->assertNull(PricebookSyncState::current()->running_since);

        $retryResult = $this->orchestrator()->run();
        $this->assertSame('no_change', $retryResult['outcome']);
    }

    public function test_401_stops_immediately_and_records_an_error_without_retrying(): void
    {
        PricebookSyncState::current()->update(['last_revision' => 100]);

        Http::fake([
            $this->baseUrl . '/head' => Http::response(json_encode(['error' => 'unauthenticated']), 401),
        ]);

        $result = $this->orchestrator()->run();

        $this->assertSame('unauthenticated', $result['outcome']);
        $state = PricebookSyncState::current();
        $this->assertSame(100, $state->last_revision);
        $this->assertNotNull($state->last_error);
        $this->assertNull($state->running_since);
    }

    public function test_400_invalid_cursor_restarts_the_run_from_since_last_revision(): void
    {
        PricebookSyncState::current()->update(['last_revision' => 100]);

        $page1 = $this->buildChangesPage([
            'from_revision' => 100,
            'to_revision' => 300,
            'complete' => true,
            'next_cursor' => null,
            'entities' => ['pb_departments' => [
                ['department_number' => '000001', 'description' => 'GROCERY', 'shift_report_flag' => 1, 'sales_summary_report' => 1, 'created_at' => '2026-01-01 00:00:00', 'updated_at' => '2026-01-01 00:00:00', 'revision' => 300],
            ]],
        ]);

        Http::fake([
            $this->baseUrl . '/head' => Http::response(json_encode(['revision' => 300]), 200, ['ETag' => '"rev-300"']),
            // First attempt at since=100 returns a page with a cursor that then
            // "expires" — but since complete=true on the very first page here,
            // we instead simulate the cursor failing on a first page that isn't
            // complete, forcing a second page request that 400s, then a full
            // restart from since=100 which this time completes directly.
            $this->baseUrl . '/changes*' => Http::sequence()
                ->push($this->buildChangesPage([
                    'from_revision' => 100, 'to_revision' => 300, 'complete' => false, 'next_cursor' => 'expired-cursor',
                ])['body'], 200)
                ->push(json_encode(['error' => 'invalid_cursor']), 400)
                ->push($page1['body'], 200),
        ]);

        $result = $this->orchestrator()->run();

        $this->assertSame('synced', $result['outcome']);
        $this->assertSame(300, PricebookSyncState::current()->last_revision);
        $this->assertSame(1, DB::table('pb_departments')->count());
    }
}
