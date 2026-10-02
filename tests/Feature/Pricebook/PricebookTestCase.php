<?php

namespace Tests\Feature\Pricebook;

use App\Services\Pricebook\ChangeApplier;
use App\Services\Pricebook\PricebookSyncClient;
use App\Services\Pricebook\PricebookSyncOrchestrator;
use App\Services\Pricebook\SnapshotLoader;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

abstract class PricebookTestCase extends TestCase
{
    use RefreshDatabase;
    use PricebookFixtures;

    protected string $baseUrl = 'https://pricebook.test/api/pos/sync';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'pricebook.base_url' => $this->baseUrl,
            'pricebook.token' => 'test-token',
            'pricebook.changes_page_limit' => 1000,
            'pricebook.snapshot_insert_chunk_size' => 500,
            'pricebook.lock_stale_minutes' => 15,
        ]);
    }

    protected function orchestrator(): PricebookSyncOrchestrator
    {
        $client = new PricebookSyncClient();

        return new PricebookSyncOrchestrator($client, new SnapshotLoader($client), new ChangeApplier());
    }
}
