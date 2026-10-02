<?php

namespace Tests\Feature\Pricebook;

use App\Models\PricebookSyncState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class SnapshotBootstrapTest extends PricebookTestCase
{
    public function test_bootstrap_loads_all_eight_tables_and_sets_revision(): void
    {
        $snapshot = $this->buildSnapshot([
            'pb_departments' => [
                ['department_number' => '000002', 'description' => 'DAIRY', 'shift_report_flag' => 1, 'sales_summary_report' => 1, 'created_at' => '2026-06-06 23:47:16', 'updated_at' => '2026-06-06 23:47:16', 'revision' => 1],
            ],
            'pb_skus' => [
                ['item_number' => '0000000000009', 'english_description' => 'Sour Patch Kids', 'price' => '3.99', 'department_number' => '000002', 'loyalty_card_eligible' => 0, 'created_at' => '2026-06-06 23:47:16', 'updated_at' => '2026-06-06 23:47:16', 'revision' => 1],
            ],
            'pb_sku_upcs' => [
                ['id' => 1, 'item_number' => '0000000000009', 'upc' => '0005770001836', 'created_at' => '2026-06-06 23:47:16', 'updated_at' => '2026-06-06 23:47:16', 'revision' => 1],
            ],
        ], revision: 1790817871000000);

        Http::fake([
            $this->baseUrl . '/snapshot' => Http::response($snapshot['gz'], 200, [
                'X-Snapshot-Revision' => (string) $snapshot['revision'],
                'X-Snapshot-Checksum' => $snapshot['checksum'],
            ]),
        ]);

        $result = $this->orchestrator()->run();

        $this->assertSame('bootstrapped', $result['outcome']);
        $this->assertSame(1790817871000000, PricebookSyncState::current()->last_revision);
        $this->assertSame(1, DB::table('pb_departments')->count());
        $this->assertSame(1, DB::table('pb_skus')->count());
        $this->assertSame(1, DB::table('pb_sku_upcs')->count());
        $this->assertSame('0005770001836', DB::table('pb_sku_upcs')->value('upc'));
        $this->assertSame(1, DB::table('pb_sku_upcs')->value('source_id'));
    }

    public function test_bootstrap_rejects_a_bad_checksum_and_leaves_data_untouched(): void
    {
        $snapshot = $this->buildSnapshot([
            'pb_departments' => [
                ['department_number' => '000002', 'description' => 'DAIRY', 'shift_report_flag' => 1, 'sales_summary_report' => 1, 'created_at' => '2026-06-06 23:47:16', 'updated_at' => '2026-06-06 23:47:16', 'revision' => 1],
            ],
        ], revision: 1790817871000000);

        Http::fake([
            $this->baseUrl . '/snapshot' => Http::response($snapshot['gz'], 200, [
                'X-Snapshot-Revision' => (string) $snapshot['revision'],
                'X-Snapshot-Checksum' => 'sha256=' . str_repeat('0', 64),
            ]),
        ]);

        $result = $this->orchestrator()->run();

        $this->assertSame('error', $result['outcome']);
        $this->assertSame(0, DB::table('pb_departments')->count());
        $this->assertNull(PricebookSyncState::current()->last_revision);
    }

    public function test_bootstrap_rejects_a_truncated_download_missing_the_end_line(): void
    {
        $snapshot = $this->buildSnapshot([
            'pb_departments' => [
                ['department_number' => '000002', 'description' => 'DAIRY', 'shift_report_flag' => 1, 'sales_summary_report' => 1, 'created_at' => '2026-06-06 23:47:16', 'updated_at' => '2026-06-06 23:47:16', 'revision' => 1],
            ],
        ], revision: 1790817871000000);

        // Truncate the NDJSON by dropping its last line (the "end" record) before re-gzipping.
        $ndjson = rtrim(gzdecode($snapshot['gz']));
        $lines = explode("\n", $ndjson);
        array_pop($lines);
        $truncatedGz = gzencode(implode("\n", $lines) . "\n");
        $truncatedChecksum = 'sha256=' . hash('sha256', $truncatedGz);

        Http::fake([
            $this->baseUrl . '/snapshot' => Http::response($truncatedGz, 200, [
                'X-Snapshot-Revision' => (string) $snapshot['revision'],
                'X-Snapshot-Checksum' => $truncatedChecksum,
            ]),
        ]);

        $result = $this->orchestrator()->run();

        $this->assertSame('error', $result['outcome']);
        $this->assertSame(0, DB::table('pb_departments')->count());
    }
}
