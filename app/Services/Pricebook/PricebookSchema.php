<?php

namespace App\Services\Pricebook;

/**
 * Canonical shape of the 8 mirrored tables, shared by the snapshot loader
 * and the change applier. The source API omits null columns from each row;
 * this is the one place that expands a row back out to every column so a
 * missing key always becomes an explicit NULL (never "leave unchanged"),
 * and so every row in a batch insert/upsert has an identical column set.
 */
class PricebookSchema
{
    public const ALL_TABLES = [
        'pb_departments',
        'pb_price_groups',
        'pb_skus',
        'pb_sku_upcs',
        'pb_sku_quantity_pricing',
        'pb_sku_linked_skus',
        'pb_sku_linkable_skus',
        'pb_price_group_quantity_pricing',
    ];

    // Parent (entity) tables keep the source's natural string primary key.
    public const PARENT_PRIMARY_KEYS = [
        'pb_departments' => 'department_number',
        'pb_price_groups' => 'price_group_number',
        'pb_skus' => 'item_number',
    ];

    // Child tables get their own auto-increment id locally; the source's
    // "id" is stored in a non-unique source_id column instead. Each is
    // mapped to the column that names its parent.
    public const CHILD_PARENT_COLUMNS = [
        'pb_sku_upcs' => 'item_number',
        'pb_sku_quantity_pricing' => 'item_number',
        'pb_sku_linked_skus' => 'item_number',
        'pb_sku_linkable_skus' => 'item_number',
        'pb_price_group_quantity_pricing' => 'price_group_number',
    ];

    // Full column list per table, as sent by the API (excluding the source
    // "id" on child tables, which is remapped to source_id separately).
    private const COLUMNS = [
        'pb_departments' => [
            'department_number', 'description', 'shift_report_flag', 'sales_summary_report',
            'owner', 'bt9000_inventory_control', 'conexxus_product_code', 'gift_card_department',
            'age_requirements', 'default_item', 'created_at', 'updated_at', 'revision',
        ],
        'pb_price_groups' => [
            'price_group_number', 'english_description', 'french_description', 'price',
            'created_at', 'updated_at', 'revision',
        ],
        'pb_skus' => [
            'item_number', 'english_description', 'french_description', 'price',
            'department_number', 'price_group_number', 'item_deposit', 'promo_code',
            'host_product_code', 'tax1', 'tax2', 'tax3', 'tax4', 'tax5', 'tax6', 'tax7', 'tax8',
            'prompt_for_price', 'item_not_active', 'tax_included_price', 'wash_type',
            'car_wash_controller_code', 'upsell_qty_car_wash', 'petro_canada_pass_code',
            'item_desc_not_on_2nd_monitor', 'ontario_rst_tax_off', 'ontario_rst_tax_on',
            'federal_baked_good_item', 'prevent_bt9000_inventory_control', 'conexxus_product_code',
            'car_wash_expiry_in_days', 'afd_car_wash_position', 'age_requirements', 'redemption_only',
            'loyalty_card_eligible', 'delivery_channel_price', 'tax_strategy_id_from_nacs', 'owner',
            'created_at', 'updated_at', 'revision',
        ],
        'pb_sku_upcs' => [
            'source_id', 'item_number', 'upc', 'created_at', 'updated_at', 'revision',
        ],
        'pb_sku_quantity_pricing' => [
            'source_id', 'item_number', 'quantity', 'price', 'created_at', 'updated_at', 'revision',
        ],
        'pb_sku_linked_skus' => [
            'source_id', 'item_number', 'linked_item_number', 'mandatory', 'created_at', 'updated_at', 'revision',
        ],
        'pb_sku_linkable_skus' => [
            'source_id', 'item_number', 'linkable_item_number', 'created_at', 'updated_at', 'revision',
        ],
        'pb_price_group_quantity_pricing' => [
            'source_id', 'price_group_number', 'quantity', 'price', 'created_at', 'updated_at', 'revision',
        ],
    ];

    public static function isChildTable(string $table): bool
    {
        return array_key_exists($table, self::CHILD_PARENT_COLUMNS);
    }

    public static function primaryKey(string $table): string
    {
        return self::PARENT_PRIMARY_KEYS[$table]
            ?? throw new \InvalidArgumentException("{$table} is not a parent table.");
    }

    public static function parentColumn(string $table): string
    {
        return self::CHILD_PARENT_COLUMNS[$table]
            ?? throw new \InvalidArgumentException("{$table} is not a child table.");
    }

    public static function columns(string $table): array
    {
        return self::COLUMNS[$table]
            ?? throw new \InvalidArgumentException("Unknown pricebook table: {$table}.");
    }

    /**
     * Expand a row exactly as the API sent it (nulls omitted) into the full
     * column set, with every missing column explicitly NULL. Child-table
     * rows have their source "id" remapped to source_id.
     */
    public static function normalizeRow(string $table, array $row): array
    {
        if (self::isChildTable($table) && array_key_exists('id', $row)) {
            $row['source_id'] = $row['id'];
            unset($row['id']);
        }

        $normalized = [];
        foreach (self::columns($table) as $column) {
            $normalized[$column] = $row[$column] ?? null;
        }

        return $normalized;
    }
}
