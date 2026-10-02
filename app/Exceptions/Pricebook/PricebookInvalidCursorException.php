<?php

namespace App\Exceptions\Pricebook;

/**
 * 400 invalid_cursor from /changes: the cursor expired, was tampered with,
 * or belongs to another token. The orchestrator must restart the run from
 * since=last_revision rather than retrying the same cursor.
 */
class PricebookInvalidCursorException extends PricebookSyncException
{
}
