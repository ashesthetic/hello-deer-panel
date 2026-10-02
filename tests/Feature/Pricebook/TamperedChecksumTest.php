<?php

namespace Tests\Feature\Pricebook;

use App\Models\PricebookSyncState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class TamperedChecksumTest extends PricebookTestCase
{
    public function test_a_tampered_changes_page_is_rejected_before_any_write(): void
    {
        PricebookSyncState::current()->update(['last_revision' => 100]);

        $page = $this->buildChangesPage([
            'from_revision' => 100,
            'to_revision' => 300,
            'complete' => true,
            'next_cursor' => null,
            'entities' => ['pb_departments' => [
                ['department_number' => '000001', 'description' => 'GROCERY', 'shift_report_flag' => 1, 'sales_summary_report' => 1, 'created_at' => '2026-01-01 00:00:00', 'updated_at' => '2026-01-01 00:00:00', 'revision' => 300],
            ]],
        ]);

        $tampered = $this->tamperChangesPageBody($page['body']);
        $this->assertNotSame($page['body'], $tampered);

        Http::fake([
            $this->baseUrl . '/head' => Http::response(json_encode(['revision' => 300]), 200, ['ETag' => '"rev-300"']),
            $this->baseUrl . '/changes*' => Http::response($tampered, 200),
        ]);

        $result = $this->orchestrator()->run();

        $this->assertSame('error', $result['outcome']);
        $this->assertSame(0, DB::table('pb_departments')->count());
        $this->assertSame(100, PricebookSyncState::current()->last_revision);
    }
}
