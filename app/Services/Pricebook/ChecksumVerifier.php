<?php

namespace App\Services\Pricebook;

use App\Exceptions\Pricebook\PricebookChecksumMismatchException;

/**
 * Verifies the two checksum schemes the Pricebook Sync API uses. Both must
 * be computed over raw bytes, never over re-serialized/re-decoded JSON.
 */
class ChecksumVerifier
{
    /**
     * /snapshot: sha256 over the downloaded .gz bytes, before decompressing.
     * $expectedHeader is the X-Snapshot-Checksum header value, e.g. "sha256=<hex>".
     */
    public static function verifySnapshotBytes(string $rawGzipBytes, string $expectedHeader): void
    {
        $expectedHex = str_contains($expectedHeader, '=')
            ? substr($expectedHeader, strpos($expectedHeader, '=') + 1)
            : $expectedHeader;

        $actualHex = hash('sha256', $rawGzipBytes);

        if (!hash_equals(strtolower($expectedHex), strtolower($actualHex))) {
            throw new PricebookChecksumMismatchException('Snapshot checksum mismatch; discarding download.');
        }
    }

    /**
     * /changes: sha256 over the raw response bytes with the checksum field
     * cut off and replaced with a closing brace:
     *   signed = body[0 : last_index_of(',"checksum":"')] + "}"
     *   assert sha256_hex(signed) == parsed.checksum
     */
    public static function verifyChangesPageChecksum(string $rawBody, string $expectedHex): void
    {
        $marker = ',"checksum":"';
        $cut = strrpos($rawBody, $marker);

        if ($cut === false) {
            throw new PricebookChecksumMismatchException('Changes page is missing the checksum field.');
        }

        $signed = substr($rawBody, 0, $cut) . '}';
        $actualHex = hash('sha256', $signed);

        if (!hash_equals(strtolower($expectedHex), strtolower($actualHex))) {
            throw new PricebookChecksumMismatchException('Changes page checksum mismatch; discarding page.');
        }
    }
}
