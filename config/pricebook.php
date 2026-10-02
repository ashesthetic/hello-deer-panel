<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Pricebook Sync API Configuration
    |--------------------------------------------------------------------------
    |
    | Connection settings for the remote, read-only Pricebook Sync API.
    | This app only ever pulls from this API — it never writes back.
    |
    */

    'base_url' => env('POS_SYNC_BASE_URL'),

    'token' => env('POS_SYNC_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | Timeouts (seconds)
    |--------------------------------------------------------------------------
    */

    'connect_timeout' => 10,

    'read_timeout' => 60,

    'snapshot_timeout' => 120,

    /*
    |--------------------------------------------------------------------------
    | Sync Behaviour
    |--------------------------------------------------------------------------
    */

    // A running_since lock older than this is considered stale and can be reclaimed.
    'lock_stale_minutes' => 15,

    // Rows per /changes page (server default 500, max 1000).
    'changes_page_limit' => 1000,

    // Rows per batch insert during a snapshot bootstrap.
    'snapshot_insert_chunk_size' => 500,
];
