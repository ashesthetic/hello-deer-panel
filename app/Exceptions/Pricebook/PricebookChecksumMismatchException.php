<?php

namespace App\Exceptions\Pricebook;

/**
 * The sha256 checksum of a /snapshot download or a /changes page did not
 * match what the server declared. The payload must be discarded, never
 * applied, and the run retried later.
 */
class PricebookChecksumMismatchException extends PricebookSyncException
{
}
