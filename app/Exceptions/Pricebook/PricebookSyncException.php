<?php

namespace App\Exceptions\Pricebook;

use RuntimeException;

/**
 * Base class for every pricebook sync failure. Catching this (rather than
 * a bare \Throwable) keeps the sync orchestrator from swallowing unrelated
 * application errors.
 */
class PricebookSyncException extends RuntimeException
{
}
