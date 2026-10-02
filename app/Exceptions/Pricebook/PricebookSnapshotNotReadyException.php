<?php

namespace App\Exceptions\Pricebook;

/**
 * 503 snapshot_not_ready from /snapshot: a fresh snapshot is still being
 * generated (up to ~5 minutes after an import). Retry later.
 */
class PricebookSnapshotNotReadyException extends PricebookSyncException
{
    public function __construct(public readonly ?int $retryAfterSeconds = null)
    {
        parent::__construct('Pricebook snapshot is not ready yet.');
    }
}
