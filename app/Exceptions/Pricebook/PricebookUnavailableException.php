<?php

namespace App\Exceptions\Pricebook;

/**
 * 503 pricebook_unavailable from /head or /changes: a pricebook import is
 * running or just failed on the server. Local data must be left untouched
 * and the next scheduled tick will retry.
 */
class PricebookUnavailableException extends PricebookSyncException
{
    public function __construct(string $reason = 'unknown', public readonly ?int $retryAfterSeconds = null)
    {
        parent::__construct("Pricebook is unavailable: {$reason}");
    }
}
