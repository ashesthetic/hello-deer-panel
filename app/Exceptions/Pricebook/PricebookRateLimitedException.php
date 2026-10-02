<?php

namespace App\Exceptions\Pricebook;

/**
 * 429 Too Many Attempts. Carries the Retry-After hint so the orchestrator
 * can back off before the next scheduled tick retries.
 */
class PricebookRateLimitedException extends PricebookSyncException
{
    public function __construct(public readonly ?int $retryAfterSeconds = null)
    {
        parent::__construct('Pricebook Sync API rate limited the request.');
    }
}
