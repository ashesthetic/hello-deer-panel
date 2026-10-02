<?php

namespace App\Services\Pricebook;

use App\Exceptions\Pricebook\PricebookSyncException;
use App\Models\PricebookSyncState;
use Illuminate\Support\Facades\DB;

/**
 * Bootstraps (or fully re-bootstraps) the local pricebook mirror from
 * GET /snapshot: verify the checksum over the raw gzip bytes, verify the
 * end-line counts, then replace all 8 tables in one transaction.
 *
 * The documented snapshot size (~400KB gzipped, ~23k NDJSON lines) is small
 * enough to decompress into memory in one call; this also keeps the loader
 * testable against Http::fake(), whose mock handler does not honour Guzzle's
 * "sink" streaming option. If the snapshot grows substantially, this class's
 * public interface (bootstrap()) would not need to change to move to a
 * disk-backed gzopen()/gzgets() stream instead.
 */
class SnapshotLoader
{
    // pb_departments/pb_skus have inbound FKs (from department_sales/item_sales,
    // added by earlier NAXML-related migrations) with cascade/set-null delete
    // behaviour. A bootstrap empties and immediately reinserts every row, so
    // those FKs are suppressed around just these two tables' replace step to
    // avoid a transient cascade-delete of unrelated sales rows. Known residual
    // limitation: a department/SKU truly removed upstream (absent from the new
    // snapshot) will leave department_sales/item_sales rows pointing at a now-
    // missing key, since FK checks are off during the delete and MySQL does not
    // retroactively validate existing rows when checks are re-enabled. This
    // window only exists during a bootstrap (first run, 409, or a manual force
    // re-sync) and self-corrects via the normal cascade/set-null behaviour the
    // next time that key is deleted through an ordinary incremental run.
    private const FK_GUARDED_TABLES = ['pb_departments', 'pb_skus'];

    public function __construct(private readonly PricebookSyncClient $client)
    {
    }

    /**
     * @return array{revision:int, counts:array<string,int>}
     */
    public function bootstrap(): array
    {
        $download = $this->client->downloadSnapshot();

        ChecksumVerifier::verifySnapshotBytes($download['body'], $download['checksum_header']);

        $decompressed = @gzdecode($download['body']);
        if ($decompressed === false) {
            throw new PricebookSyncException('Unable to gunzip the snapshot download.');
        }

        [$tables, $counts, $metaRevision] = $this->parseNdjson($decompressed);

        if ($metaRevision !== $download['revision']) {
            throw new PricebookSyncException('Snapshot revision mismatch between header and NDJSON body.');
        }

        DB::transaction(function () use ($tables, $download) {
            $this->replaceAllTables($tables);

            PricebookSyncState::current()->update([
                'last_revision' => $download['revision'],
                'last_snapshot_revision' => $download['revision'],
                'head_etag' => null,
            ]);
        });

        return ['revision' => $download['revision'], 'counts' => $counts];
    }

    /**
     * @return array{0: array<string, array<int, array<string, mixed>>>, 1: array<string, int>, 2: ?int}
     */
    private function parseNdjson(string $ndjson): array
    {
        $tables = array_fill_keys(PricebookSchema::ALL_TABLES, []);
        $counts = array_fill_keys(PricebookSchema::ALL_TABLES, 0);
        $metaRevision = null;
        $endCounts = null;
        $endRevision = null;

        foreach (preg_split('/\R/', $ndjson) as $line) {
            if ($line === '') {
                continue;
            }

            $doc = json_decode($line, true);
            if (!is_array($doc)) {
                throw new PricebookSyncException('Malformed snapshot line (not valid JSON).');
            }

            if (($doc['type'] ?? null) === 'meta') {
                $metaRevision = (int) $doc['revision'];
                continue;
            }

            if (($doc['type'] ?? null) === 'end') {
                $endRevision = (int) $doc['revision'];
                $endCounts = $doc['counts'] ?? [];
                continue;
            }

            $table = $doc['table'] ?? null;
            $row = $doc['row'] ?? null;

            if (!is_string($table) || !is_array($row) || !array_key_exists($table, $tables)) {
                throw new PricebookSyncException('Malformed snapshot line (unknown table or missing row).');
            }

            $tables[$table][] = PricebookSchema::normalizeRow($table, $row);
            $counts[$table]++;
        }

        if ($endCounts === null || $endRevision === null) {
            throw new PricebookSyncException('Snapshot is missing its end record; the download was truncated.');
        }

        foreach ($endCounts as $table => $declared) {
            if (($counts[$table] ?? null) !== $declared) {
                throw new PricebookSyncException(
                    "Snapshot row count mismatch for {$table}: expected {$declared}, read " . ($counts[$table] ?? 0) . '.'
                );
            }
        }

        return [$tables, $counts, $metaRevision ?? $endRevision];
    }

    /**
     * @param array<string, array<int, array<string, mixed>>> $tables
     */
    private function replaceAllTables(array $tables): void
    {
        $this->withForeignKeyChecksDisabled(function () use ($tables) {
            foreach (self::FK_GUARDED_TABLES as $table) {
                $this->replaceTable($table, $tables[$table] ?? []);
            }
        });

        foreach (PricebookSchema::ALL_TABLES as $table) {
            if (in_array($table, self::FK_GUARDED_TABLES, true)) {
                continue;
            }
            $this->replaceTable($table, $tables[$table] ?? []);
        }
    }

    /** MySQL in production, SQLite (no-op equivalent) in the test suite. */
    private function withForeignKeyChecksDisabled(\Closure $callback): void
    {
        $driver = DB::connection()->getDriverName();

        $disable = match ($driver) {
            'mysql' => 'SET FOREIGN_KEY_CHECKS=0',
            'sqlite' => 'PRAGMA foreign_keys = OFF',
            default => null,
        };
        $enable = match ($driver) {
            'mysql' => 'SET FOREIGN_KEY_CHECKS=1',
            'sqlite' => 'PRAGMA foreign_keys = ON',
            default => null,
        };

        if ($disable !== null) {
            DB::statement($disable);
        }

        try {
            $callback();
        } finally {
            if ($enable !== null) {
                DB::statement($enable);
            }
        }
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     */
    private function replaceTable(string $table, array $rows): void
    {
        DB::table($table)->delete();

        $chunkSize = (int) config('pricebook.snapshot_insert_chunk_size', 500);
        foreach (array_chunk($rows, max(1, $chunkSize)) as $chunk) {
            DB::table($table)->insert($chunk);
        }
    }
}
