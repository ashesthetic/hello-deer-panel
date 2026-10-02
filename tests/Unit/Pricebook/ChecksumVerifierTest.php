<?php

namespace Tests\Unit\Pricebook;

use App\Exceptions\Pricebook\PricebookChecksumMismatchException;
use App\Services\Pricebook\ChecksumVerifier;
use PHPUnit\Framework\TestCase;

class ChecksumVerifierTest extends TestCase
{
    public function test_snapshot_bytes_checksum_accepts_a_correct_sha256(): void
    {
        $bytes = gzencode('hello world');
        $header = 'sha256=' . hash('sha256', $bytes);

        ChecksumVerifier::verifySnapshotBytes($bytes, $header);
        $this->addToAssertionCount(1); // no exception thrown
    }

    public function test_snapshot_bytes_checksum_rejects_a_mismatch(): void
    {
        $this->expectException(PricebookChecksumMismatchException::class);

        ChecksumVerifier::verifySnapshotBytes(gzencode('hello world'), 'sha256=' . str_repeat('0', 64));
    }

    public function test_changes_page_checksum_uses_the_exact_cut_and_replace_algorithm(): void
    {
        $base = ['a' => 1, 'b' => [1, 2, 3]];
        $baseJson = json_encode($base);
        $hash = hash('sha256', $baseJson);
        $body = substr($baseJson, 0, -1) . ',"checksum":"' . $hash . '"}';

        ChecksumVerifier::verifyChangesPageChecksum($body, $hash);
        $this->addToAssertionCount(1); // no exception thrown
    }

    public function test_changes_page_checksum_rejects_a_mismatch(): void
    {
        $this->expectException(PricebookChecksumMismatchException::class);

        $body = '{"a":1,"checksum":"deadbeef"}';
        ChecksumVerifier::verifyChangesPageChecksum($body, 'deadbeef');
    }
}
