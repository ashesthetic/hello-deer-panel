<?php

namespace App\Exceptions\Pricebook;

/**
 * 401 from the Pricebook Sync API: the token is missing, wrong, or revoked.
 * The orchestrator must stop retrying and surface this for an admin to fix.
 */
class PricebookUnauthenticatedException extends PricebookSyncException
{
}
