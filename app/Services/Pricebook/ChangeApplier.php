<?php

namespace App\Services\Pricebook;

use Illuminate\Support\Facades\DB;

/**
 * Applies one /changes page, in the exact order the sync spec requires:
 *   1. entities   — upsert by primary key, writing every column (missing => NULL)
 *   2. child_sets — replace each parent's full child-row set (empty list => delete all)
 *   3. deletions  — pb_skus/pb_price_groups cascade to their own child tables
 *                   (there is no DB-level FK between mirror tables, so this is
 *                   coded explicitly); pb_departments deletes touch nothing else.
 *
 * Caller is responsible for the surrounding transaction and for only persisting
 * the new revision after a page with complete=true has been applied.
 */
class ChangeApplier
{
    private const SKU_CHILD_TABLES = [
        'pb_sku_upcs',
        'pb_sku_quantity_pricing',
        'pb_sku_linked_skus',
        'pb_sku_linkable_skus',
    ];

    /**
     * @param array<string, mixed> $page decoded /changes page body
     * @return array{entities:int, child_rows:int, deletions:int}
     */
    public function apply(array $page): array
    {
        $entityCount = $this->applyEntities($page['entities'] ?? []);
        $childRowCount = $this->applyChildSets($page['child_sets'] ?? []);
        $deletionCount = $this->applyDeletions($page['deletions'] ?? []);

        return [
            'entities' => $entityCount,
            'child_rows' => $childRowCount,
            'deletions' => $deletionCount,
        ];
    }

    /**
     * @param array<string, array<int, array<string, mixed>>> $entities
     */
    private function applyEntities(array $entities): int
    {
        $count = 0;

        foreach ($entities as $table => $rows) {
            if (empty($rows)) {
                continue;
            }

            $pk = PricebookSchema::primaryKey($table);
            $normalized = array_map(
                fn (array $row) => PricebookSchema::normalizeRow($table, $row),
                $rows
            );

            $updateColumns = array_values(array_diff(PricebookSchema::columns($table), [$pk]));

            DB::table($table)->upsert($normalized, [$pk], $updateColumns);
            $count += count($normalized);
        }

        return $count;
    }

    /**
     * @param array<string, array<string, array<int, array<string, mixed>>>> $childSets
     */
    private function applyChildSets(array $childSets): int
    {
        $count = 0;

        foreach ($childSets as $table => $byParentKey) {
            $parentColumn = PricebookSchema::parentColumn($table);

            foreach ($byParentKey as $parentKey => $rows) {
                DB::table($table)->where($parentColumn, (string) $parentKey)->delete();

                if (empty($rows)) {
                    continue;
                }

                $normalized = array_map(
                    fn (array $row) => PricebookSchema::normalizeRow($table, $row),
                    $rows
                );

                DB::table($table)->insert($normalized);
                $count += count($normalized);
            }
        }

        return $count;
    }

    /**
     * @param array<int, array{entity:string, key:string, revision:int}> $deletions
     */
    private function applyDeletions(array $deletions): int
    {
        foreach ($deletions as $deletion) {
            $entity = $deletion['entity'];
            $key = (string) $deletion['key'];

            match ($entity) {
                'pb_skus' => $this->deleteSku($key),
                'pb_price_groups' => $this->deletePriceGroup($key),
                'pb_departments' => DB::table('pb_departments')->where('department_number', $key)->delete(),
                default => null,
            };
        }

        return count($deletions);
    }

    private function deleteSku(string $itemNumber): void
    {
        foreach (self::SKU_CHILD_TABLES as $childTable) {
            DB::table($childTable)->where('item_number', $itemNumber)->delete();
        }

        DB::table('pb_skus')->where('item_number', $itemNumber)->delete();
    }

    private function deletePriceGroup(string $priceGroupNumber): void
    {
        DB::table('pb_price_group_quantity_pricing')->where('price_group_number', $priceGroupNumber)->delete();
        DB::table('pb_price_groups')->where('price_group_number', $priceGroupNumber)->delete();
    }
}
