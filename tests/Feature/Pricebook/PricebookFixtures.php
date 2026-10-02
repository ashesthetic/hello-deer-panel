<?php

namespace Tests\Feature\Pricebook;

use App\Services\Pricebook\PricebookSchema;

/**
 * Builds spec-accurate fixture payloads (with correct checksums) for the
 * Pricebook Sync API's /snapshot and /changes responses, so tests exercise
 * the real verification logic instead of stubbing it out.
 */
trait PricebookFixtures
{
    /**
     * @param array<string, array<int, array<string, mixed>>> $rowsByTable
     * @return array{gz: string, checksum: string, revision: int}
     */
    protected function buildSnapshot(array $rowsByTable, int $revision): array
    {
        $lines = [];
        $lines[] = json_encode([
            'type' => 'meta',
            'revision' => $revision,
            'generated_at' => '2026-01-01T00:00:00+00:00',
            'tables' => PricebookSchema::ALL_TABLES,
        ]);

        $counts = [];
        foreach (PricebookSchema::ALL_TABLES as $table) {
            $rows = $rowsByTable[$table] ?? [];
            $counts[$table] = count($rows);
            foreach ($rows as $row) {
                $lines[] = json_encode(['table' => $table, 'row' => $row]);
            }
        }

        $lines[] = json_encode(['type' => 'end', 'revision' => $revision, 'counts' => $counts]);

        $ndjson = implode("\n", $lines) . "\n";
        $gz = gzencode($ndjson);
        $checksum = 'sha256=' . hash('sha256', $gz);

        return ['gz' => $gz, 'checksum' => $checksum, 'revision' => $revision];
    }

    /**
     * Builds one /changes page body with a correct trailing checksum field,
     * computed the same way the server does: sha256 over the JSON body with
     * the checksum field itself removed.
     *
     * @return array{body: string, decoded: array<string, mixed>}
     */
    protected function buildChangesPage(array $overrides = []): array
    {
        $base = array_merge([
            'from_revision' => 1,
            'to_revision' => 2,
            'complete' => true,
            'next_cursor' => null,
            'entities' => (object) [],
            'child_sets' => (object) [],
            'deletions' => [],
        ], $overrides);

        $baseJson = json_encode($base);
        $hash = hash('sha256', $baseJson);
        $body = substr($baseJson, 0, -1) . ',"checksum":"' . $hash . '"}';

        return ['body' => $body, 'decoded' => json_decode($body, true)];
    }

    protected function tamperChangesPageBody(string $body): string
    {
        // Mutate the to_revision value (well before the checksum field) so the
        // declared checksum no longer matches, while keeping the JSON valid.
        return preg_replace('/"to_revision":(\d+)/', '"to_revision":$19', $body, 1);
    }
}
