<?php

namespace App\Exceptions\Pricebook;

/**
 * 422 invalid_request: bad parameters sent by our own client. This is a bug
 * in this app, not a transient condition — log and alert rather than retry.
 */
class PricebookInvalidRequestException extends PricebookSyncException
{
}
