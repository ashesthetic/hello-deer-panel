<?php

namespace Tests\Feature\Pricebook;

use App\Services\Pricebook\ChangeApplier;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Exercises ChangeApplier directly (no HTTP involved) so each scenario can
 * set up precise before-state and assert precise after-state.
 */
class ChangeApplierTest extends TestCase
{
    use RefreshDatabase;

    private ChangeApplier $applier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->applier = new ChangeApplier();
    }

    public function test_a_column_missing_from_an_entity_row_becomes_null_even_if_previously_set(): void
    {
        DB::table('pb_skus')->insert([
            'item_number' => '0000000000009',
            'english_description' => 'Old Description',
            'price' => '3.99',
            'department_number' => '000004',
            'price_group_number' => '0000000000005',
            'loyalty_card_eligible' => 0,
        ]);

        $this->applier->apply([
            'entities' => [
                'pb_skus' => [[
                    // price_group_number intentionally omitted (now null upstream)
                    'item_number' => '0000000000009',
                    'english_description' => 'New Description',
                    'price' => '4.99',
                    'department_number' => '000004',
                    'loyalty_card_eligible' => 0,
                ]],
            ],
            'child_sets' => [],
            'deletions' => [],
        ]);

        $sku = DB::table('pb_skus')->where('item_number', '0000000000009')->first();
        $this->assertSame('New Description', $sku->english_description);
        $this->assertNull($sku->price_group_number);
    }

    public function test_child_set_replace_including_empty_list_deletes_all(): void
    {
        DB::table('pb_sku_upcs')->insert([
            ['item_number' => '0000000000009', 'upc' => '111', 'source_id' => 1],
            ['item_number' => '0000000000009', 'upc' => '222', 'source_id' => 2],
        ]);

        $this->applier->apply([
            'entities' => [],
            'child_sets' => [
                'pb_sku_upcs' => [
                    '0000000000009' => [
                        ['id' => 1, 'item_number' => '0000000000009', 'upc' => '111'],
                    ],
                ],
            ],
            'deletions' => [],
        ]);

        $this->assertSame(['111'], DB::table('pb_sku_upcs')->where('item_number', '0000000000009')->pluck('upc')->all());

        // Now replace with an empty list: all rows for this parent must be removed.
        $this->applier->apply([
            'entities' => [],
            'child_sets' => [
                'pb_sku_upcs' => [
                    '0000000000009' => [],
                ],
            ],
            'deletions' => [],
        ]);

        $this->assertSame(0, DB::table('pb_sku_upcs')->where('item_number', '0000000000009')->count());
    }

    public function test_upc_move_between_skus_within_one_page_applies_without_constraint_errors(): void
    {
        // UPC "0005770001836" currently belongs to SKU A with source row id 1.
        DB::table('pb_sku_upcs')->insert([
            'source_id' => 1,
            'item_number' => '0000000000001',
            'upc' => '0005770001836',
        ]);

        // Within one page, SKU B's complete set (containing the UPC) is applied
        // before SKU A's complete set (which no longer has it) — the order the
        // spec says can happen when parent keys sort B before A.
        $this->applier->apply([
            'entities' => [],
            'child_sets' => [
                'pb_sku_upcs' => [
                    '0000000000002' => [
                        ['id' => 1, 'item_number' => '0000000000002', 'upc' => '0005770001836'],
                    ],
                    '0000000000001' => [],
                ],
            ],
            'deletions' => [],
        ]);

        $rows = DB::table('pb_sku_upcs')->get();
        $this->assertCount(1, $rows);
        $this->assertSame('0000000000002', $rows->first()->item_number);
        $this->assertSame('0005770001836', $rows->first()->upc);
    }

    public function test_sku_deletion_cascades_its_four_child_tables(): void
    {
        DB::table('pb_skus')->insert([
            'item_number' => '0000000000009', 'english_description' => 'X', 'price' => '1.00',
            'department_number' => '000001', 'loyalty_card_eligible' => 0,
        ]);
        DB::table('pb_sku_upcs')->insert(['item_number' => '0000000000009', 'upc' => '111']);
        DB::table('pb_sku_quantity_pricing')->insert(['item_number' => '0000000000009', 'quantity' => 2, 'price' => '1.80']);
        DB::table('pb_sku_linked_skus')->insert(['item_number' => '0000000000009', 'linked_item_number' => '0000000000010', 'mandatory' => 1]);
        DB::table('pb_sku_linkable_skus')->insert(['item_number' => '0000000000009', 'linkable_item_number' => '0000000000011']);

        $this->applier->apply([
            'entities' => [],
            'child_sets' => [],
            'deletions' => [['entity' => 'pb_skus', 'key' => '0000000000009', 'revision' => 99]],
        ]);

        $this->assertSame(0, DB::table('pb_skus')->count());
        $this->assertSame(0, DB::table('pb_sku_upcs')->count());
        $this->assertSame(0, DB::table('pb_sku_quantity_pricing')->count());
        $this->assertSame(0, DB::table('pb_sku_linked_skus')->count());
        $this->assertSame(0, DB::table('pb_sku_linkable_skus')->count());
    }

    public function test_department_and_price_group_deletion_do_not_cascade_to_skus(): void
    {
        DB::table('pb_departments')->insert([
            'department_number' => '000001', 'description' => 'GROCERY',
            'shift_report_flag' => 1, 'sales_summary_report' => 1,
        ]);
        DB::table('pb_price_groups')->insert([
            'price_group_number' => '0000000000005', 'english_description' => 'Choco Bar', 'price' => '1.99',
        ]);
        DB::table('pb_price_group_quantity_pricing')->insert([
            'price_group_number' => '0000000000005', 'quantity' => 2, 'price' => '3.50',
        ]);
        DB::table('pb_skus')->insert([
            'item_number' => '0000000000009', 'english_description' => 'X', 'price' => '1.00',
            'department_number' => '000001', 'price_group_number' => '0000000000005', 'loyalty_card_eligible' => 0,
        ]);

        $this->applier->apply([
            'entities' => [],
            'child_sets' => [],
            'deletions' => [
                ['entity' => 'pb_departments', 'key' => '000001', 'revision' => 1],
                ['entity' => 'pb_price_groups', 'key' => '0000000000005', 'revision' => 2],
            ],
        ]);

        $this->assertSame(0, DB::table('pb_departments')->count());
        $this->assertSame(0, DB::table('pb_price_groups')->count());
        $this->assertSame(0, DB::table('pb_price_group_quantity_pricing')->count());
        // The SKU referencing both is untouched.
        $this->assertSame(1, DB::table('pb_skus')->count());
    }

    public function test_money_stays_an_exact_decimal_string_with_no_float_drift(): void
    {
        $this->applier->apply([
            'entities' => [
                'pb_price_groups' => [[
                    'price_group_number' => '0000000000005',
                    'english_description' => 'Choco Bar',
                    'price' => '0.10',
                ]],
            ],
            'child_sets' => [],
            'deletions' => [],
        ]);

        $this->assertSame('0.10', DB::table('pb_price_groups')->value('price'));
    }

    public function test_leading_zeros_survive_in_keys(): void
    {
        $this->applier->apply([
            'entities' => [
                'pb_departments' => [[
                    'department_number' => '000002',
                    'description' => 'DAIRY',
                    'shift_report_flag' => 1,
                    'sales_summary_report' => 1,
                ]],
                'pb_skus' => [[
                    'item_number' => '0000000000009',
                    'english_description' => 'Sour Patch Kids',
                    'price' => '3.99',
                    'department_number' => '000002',
                    'loyalty_card_eligible' => 0,
                ]],
            ],
            'child_sets' => [],
            'deletions' => [],
        ]);

        $this->assertSame('000002', DB::table('pb_departments')->value('department_number'));
        $this->assertSame('0000000000009', DB::table('pb_skus')->value('item_number'));
    }
}
