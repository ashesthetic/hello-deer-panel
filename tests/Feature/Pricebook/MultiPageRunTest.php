<?php

namespace Tests\Feature\Pricebook;

use App\Models\PricebookSyncState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class MultiPageRunTest extends PricebookTestCase
{
    public function test_revision_only_advances_after_the_complete_page_and_rerun_is_idempotent(): void
    {
        PricebookSyncState::current()->update(['last_revision' => 100]);

        $page1 = $this->buildChangesPage([
            'from_revision' => 100,
            'to_revision' => 300,
            'complete' => false,
            'next_cursor' => 'cursor-abc',
            'entities' => ['pb_departments' => [
                ['department_number' => '000001', 'description' => 'GROCERY', 'shift_report_flag' => 1, 'sales_summary_report' => 1, 'created_at' => '2026-01-01 00:00:00', 'updated_at' => '2026-01-01 00:00:00', 'revision' => 200],
            ]],
        ]);
        $page2 = $this->buildChangesPage([
            'from_revision' => 100,
            'to_revision' => 300,
            'complete' => true,
            'next_cursor' => null,
            'entities' => ['pb_departments' => [
                ['department_number' => '000002', 'description' => 'DAIRY', 'shift_report_flag' => 1, 'sales_summary_report' => 1, 'created_at' => '2026-01-01 00:00:00', 'updated_at' => '2026-01-01 00:00:00', 'revision' => 300],
            ]],
        ]);

        Http::fake([
            $this->baseUrl . '/head' => Http::response(json_encode(['revision' => 300]), 200, ['ETag' => '"rev-300"']),
            $this->baseUrl . '/changes*' => Http::sequence()
                ->push($page1['body'], 200)
                ->push($page2['body'], 200),
        ]);

        $result = $this->orchestrator()->run();

        $this->assertSame('synced', $result['outcome']);
        $this->assertSame(300, PricebookSyncState::current()->last_revision);
        $this->assertSame(2, DB::table('pb_departments')->count());
    }

    public function test_a_run_interrupted_after_page_one_resumes_and_ends_in_the_same_final_state(): void
    {
        PricebookSyncState::current()->update(['last_revision' => 100]);

        $page1 = $this->buildChangesPage([
            'from_revision' => 100,
            'to_revision' => 300,
            'complete' => false,
            'next_cursor' => 'cursor-abc',
            'entities' => ['pb_departments' => [
                ['department_number' => '000001', 'description' => 'GROCERY', 'shift_report_flag' => 1, 'sales_summary_report' => 1, 'created_at' => '2026-01-01 00:00:00', 'updated_at' => '2026-01-01 00:00:00', 'revision' => 200],
            ]],
        ]);

        $page2 = $this->buildChangesPage([
            'from_revision' => 100,
            'to_revision' => 300,
            'complete' => true,
            'next_cursor' => null,
            'entities' => ['pb_departments' => [
                ['department_number' => '000002', 'description' => 'DAIRY', 'shift_report_flag' => 1, 'sales_summary_report' => 1, 'created_at' => '2026-01-01 00:00:00', 'updated_at' => '2026-01-01 00:00:00', 'revision' => 300],
            ]],
        ]);

        // One combined sequence spans both runs: first run gets page 1 then a
        // 500 (simulating a crash/network drop mid-run); the second run restarts
        // from since=100 (the persisted revision), replaying page 1 again
        // (idempotent upsert) then completing with page 2.
        Http::fake([
            $this->baseUrl . '/head' => Http::response(json_encode(['revision' => 300]), 200, ['ETag' => '"rev-300"']),
            $this->baseUrl . '/changes*' => Http::sequence()
                ->push($page1['body'], 200)
                ->push('Internal Server Error', 500)
                ->push($page1['body'], 200)
                ->push($page2['body'], 200),
        ]);

        $firstResult = $this->orchestrator()->run();
        $this->assertSame('error', $firstResult['outcome']);
        // Revision has NOT advanced because page 1 was not complete=true.
        $this->assertSame(100, PricebookSyncState::current()->last_revision);
        $this->assertSame(1, DB::table('pb_departments')->count());

        $secondResult = $this->orchestrator()->run();

        $this->assertSame('synced', $secondResult['outcome']);
        $this->assertSame(300, PricebookSyncState::current()->last_revision);
        // Same final state as an uninterrupted run would have produced.
        $this->assertSame(2, DB::table('pb_departments')->count());
    }
}
