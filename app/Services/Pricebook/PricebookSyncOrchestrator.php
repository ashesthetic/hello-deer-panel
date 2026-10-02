<?php

namespace App\Services\Pricebook;

use App\Exceptions\Pricebook\PricebookInvalidCursorException;
use App\Exceptions\Pricebook\PricebookRateLimitedException;
use App\Exceptions\Pricebook\PricebookSnapshotNotReadyException;
use App\Exceptions\Pricebook\PricebookSnapshotRequiredException;
use App\Exceptions\Pricebook\PricebookSyncException;
use App\Exceptions\Pricebook\PricebookUnauthenticatedException;
use App\Exceptions\Pricebook\PricebookUnavailableException;
use App\Models\PricebookSyncState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Implements the sync algorithm's main loop: check /head, bootstrap via
 * /snapshot when needed, otherwise page through /changes and apply each
 * page transactionally, only advancing last_revision once a complete page
 * has committed. Never calls /changes?since=0 — a null last_revision always
 * goes straight to bootstrap.
 */
class PricebookSyncOrchestrator
{
    public function __construct(
        private readonly PricebookSyncClient $client,
        private readonly SnapshotLoader $snapshotLoader,
        private readonly ChangeApplier $changeApplier,
    ) {
    }

    /**
     * @return array<string, mixed> run summary (also logged as one line)
     */
    public function run(bool $forceFullResync = false): array
    {
        $startedAt = microtime(true);
        $state = PricebookSyncState::current();

        if (!$this->acquireLock($state)) {
            return $this->summary('skipped', 'Another sync is already running.', $startedAt);
        }

        if ($forceFullResync) {
            $state->update(['last_revision' => null, 'head_etag' => null]);
        }

        try {
            $result = $state->last_revision === null
                ? $this->bootstrap($state)
                : $this->syncIncrementally($state);

            $state->update([
                'running_since' => null,
                'last_success_at' => now(),
                'last_error' => null,
                'last_error_at' => null,
            ]);

            return $this->summary($result['outcome'], null, $startedAt, $result);
        } catch (PricebookUnauthenticatedException $e) {
            // Fatal: stop and surface for an admin. No retry.
            $this->recordFailure($state, $e);
            Log::critical('pricebook_sync.unauthenticated', ['message' => $e->getMessage()]);
            return $this->summary('unauthenticated', $e->getMessage(), $startedAt);
        } catch (PricebookSnapshotRequiredException $e) {
            // Caller's since was rejected mid-run; fall back to a fresh bootstrap now.
            try {
                $result = $this->bootstrap($state);
                $state->update([
                    'running_since' => null,
                    'last_success_at' => now(),
                    'last_error' => null,
                    'last_error_at' => null,
                ]);
                return $this->summary($result['outcome'], null, $startedAt, $result);
            } catch (\Throwable $inner) {
                $this->recordFailure($state, $inner);
                return $this->summary('error', $inner->getMessage(), $startedAt);
            }
        } catch (PricebookRateLimitedException|PricebookUnavailableException|PricebookSnapshotNotReadyException $e) {
            // Transient: leave data untouched, let the next scheduled tick retry.
            $this->recordFailure($state, $e);
            return $this->summary('retry_later', $e->getMessage(), $startedAt);
        } catch (\Throwable $e) {
            $this->recordFailure($state, $e);
            Log::error('pricebook_sync.error', ['message' => $e->getMessage()]);
            return $this->summary('error', $e->getMessage(), $startedAt);
        }
    }

    private function bootstrap(PricebookSyncState $state): array
    {
        $result = $this->snapshotLoader->bootstrap();

        $this->log('bootstrap', [
            'to_revision' => $result['revision'],
            'counts' => $result['counts'],
        ]);

        return ['outcome' => 'bootstrapped', 'revision' => $result['revision'], 'counts' => $result['counts']];
    }

    private function syncIncrementally(PricebookSyncState $state): array
    {
        $head = $this->client->head($state->head_etag);

        if ($head->notModified) {
            return ['outcome' => 'no_change'];
        }

        $state->update(['head_etag' => $head->etag]);

        if ($head->revision === $state->last_revision) {
            return ['outcome' => 'no_change'];
        }

        return $this->runChanges($state);
    }

    private function runChanges(PricebookSyncState $state, int $restartCount = 0): array
    {
        if ($restartCount > 5) {
            throw new PricebookSyncException('Too many consecutive invalid_cursor restarts; aborting this run.');
        }

        $since = $state->last_revision;
        $cursor = null;
        $pages = 0;
        $totals = ['entities' => 0, 'child_rows' => 0, 'deletions' => 0];
        $fromRevision = $since;
        $toRevision = null;

        try {
            do {
                $page = $this->client->changesPage($cursor === null ? $since : null, $cursor, (int) config('pricebook.changes_page_limit', 1000));
                $pages++;
                $toRevision = $page['to_revision'];

                DB::transaction(function () use ($page, $state, &$totals) {
                    $applied = $this->changeApplier->apply($page);
                    $totals['entities'] += $applied['entities'];
                    $totals['child_rows'] += $applied['child_rows'];
                    $totals['deletions'] += $applied['deletions'];

                    if ($page['complete']) {
                        $state->update(['last_revision' => $page['to_revision']]);
                    }
                });

                $cursor = $page['next_cursor'] ?? null;
            } while (!$page['complete']);
        } catch (PricebookInvalidCursorException $e) {
            // Cursor expired/invalid: restart the whole run from since=last_revision
            // (never persist a cursor across restarts) rather than retrying it.
            Log::warning('pricebook_sync.invalid_cursor_restart', ['message' => $e->getMessage()]);
            return $this->runChanges($state->refresh(), $restartCount + 1);
        }

        $this->log('changes', [
            'from_revision' => $fromRevision,
            'to_revision' => $toRevision,
            'pages' => $pages,
            ...$totals,
        ]);

        return ['outcome' => 'synced', 'pages' => $pages, 'from_revision' => $fromRevision, 'to_revision' => $toRevision, ...$totals];
    }

    private function acquireLock(PricebookSyncState $state): bool
    {
        $staleMinutes = (int) config('pricebook.lock_stale_minutes', 15);

        if ($state->running_since !== null && $state->running_since->gt(now()->subMinutes($staleMinutes))) {
            return false;
        }

        $state->update(['running_since' => now()]);

        return true;
    }

    private function recordFailure(PricebookSyncState $state, \Throwable $e): void
    {
        $state->update([
            'running_since' => null,
            'last_error' => $e->getMessage(),
            'last_error_at' => now(),
        ]);
    }

    private function log(string $phase, array $context): void
    {
        Log::info("pricebook_sync.{$phase}", $context);
    }

    private function summary(string $outcome, ?string $error, float $startedAt, array $extra = []): array
    {
        return [
            'outcome' => $outcome,
            'error' => $error,
            'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
            ...$extra,
        ];
    }
}
