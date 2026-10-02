<?php

namespace App\Exceptions\Pricebook;

/**
 * 409 from /changes: our last_revision can no longer be served incrementally.
 * The orchestrator must fall back to a full snapshot bootstrap.
 */
class PricebookSnapshotRequiredException extends PricebookSyncException
{
}
