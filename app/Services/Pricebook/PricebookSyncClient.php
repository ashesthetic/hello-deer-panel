<?php

namespace App\Services\Pricebook;

use App\Exceptions\Pricebook\PricebookChecksumMismatchException;
use App\Exceptions\Pricebook\PricebookInvalidCursorException;
use App\Exceptions\Pricebook\PricebookInvalidRequestException;
use App\Exceptions\Pricebook\PricebookRateLimitedException;
use App\Exceptions\Pricebook\PricebookSnapshotNotReadyException;
use App\Exceptions\Pricebook\PricebookSnapshotRequiredException;
use App\Exceptions\Pricebook\PricebookSyncException;
use App\Exceptions\Pricebook\PricebookUnauthenticatedException;
use App\Exceptions\Pricebook\PricebookUnavailableException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Thin wrapper around the three read-only Pricebook Sync API endpoints.
 * Never writes back to the API. Never logs the bearer token: callers only
 * ever see typed results/exceptions, not raw headers.
 */
class PricebookSyncClient
{
    private readonly string $baseUrl;
    private readonly string $token;

    public function __construct(?string $baseUrl = null, ?string $token = null)
    {
        $this->baseUrl = $baseUrl ?? (string) config('pricebook.base_url');
        $this->token = $token ?? (string) config('pricebook.token');
    }

    public function head(?string $etag): HeadResult
    {
        $request = $this->request($this->readTimeout());

        if ($etag !== null) {
            $request = $request->withHeaders(['If-None-Match' => $etag]);
        }

        $response = $request->get($this->url('/head'));

        if ($response->status() === 304) {
            return HeadResult::notModified();
        }

        $this->throwForStatus($response);

        return HeadResult::ok((int) $response->json('revision'), $response->header('ETag') ?: null);
    }

    /**
     * Fetches one page of /changes and verifies its checksum. Pass either
     * $since (first page) or $cursor (subsequent pages), never both.
     *
     * @return array<string, mixed> the decoded page body
     */
    public function changesPage(?int $since, ?string $cursor, int $limit): array
    {
        $params = ['limit' => $limit];

        if ($cursor !== null) {
            $params['cursor'] = $cursor;
        } else {
            $params['since'] = $since;
        }

        $response = $this->request($this->readTimeout())->get($this->url('/changes'), $params);

        $this->throwForStatus($response);

        $rawBody = $response->body();
        $page = json_decode($rawBody, true);

        if (!is_array($page) || !isset($page['checksum'])) {
            throw new PricebookSyncException('Malformed /changes response: missing checksum.');
        }

        ChecksumVerifier::verifyChangesPageChecksum($rawBody, $page['checksum']);

        return $page;
    }

    /**
     * Downloads the full snapshot and returns its raw (still-gzipped) bytes
     * plus the metadata needed to verify and apply it. The body is small
     * (~400KB gzipped today) so it is safe to hold in memory once.
     */
    public function downloadSnapshot(): array
    {
        $response = $this->request($this->snapshotTimeout())->get($this->url('/snapshot'));

        $this->throwForStatus($response, forSnapshot: true);

        $checksumHeader = $response->header('X-Snapshot-Checksum');
        $revision = $response->header('X-Snapshot-Revision');

        if (!$checksumHeader || !$revision) {
            throw new PricebookSyncException('Snapshot response is missing required headers.');
        }

        return [
            'body' => $response->body(),
            'revision' => (int) $revision,
            'checksum_header' => $checksumHeader,
        ];
    }

    private function request(int $timeout): PendingRequest
    {
        return Http::withToken($this->token)
            ->acceptJson()
            ->withOptions(['allow_redirects' => false])
            ->connectTimeout((int) config('pricebook.connect_timeout', 10))
            ->timeout($timeout);
    }

    private function url(string $path): string
    {
        return rtrim($this->baseUrl, '/') . $path;
    }

    private function readTimeout(): int
    {
        return (int) config('pricebook.read_timeout', 60);
    }

    private function snapshotTimeout(): int
    {
        return (int) config('pricebook.snapshot_timeout', 120);
    }

    private function throwForStatus(Response $response, bool $forSnapshot = false): void
    {
        if ($response->successful()) {
            return;
        }

        $status = $response->status();
        $error = (string) ($response->json('error') ?? '');
        $retryAfter = $response->header('Retry-After');
        $retryAfter = $retryAfter !== null ? (int) $retryAfter : null;

        match (true) {
            $status === 400 => throw new PricebookInvalidCursorException(
                "Pricebook /changes rejected the cursor: {$error}"
            ),
            $status === 401 => throw new PricebookUnauthenticatedException(
                'Pricebook Sync API token is missing, invalid, or revoked.'
            ),
            $status === 409 => throw new PricebookSnapshotRequiredException(
                "Pricebook requires a full snapshot bootstrap: {$error}"
            ),
            $status === 422 => throw new PricebookInvalidRequestException(
                "Pricebook Sync API rejected the request: {$error}"
            ),
            $status === 429 => throw new PricebookRateLimitedException($retryAfter),
            $status === 503 && $error === 'snapshot_not_ready' => throw new PricebookSnapshotNotReadyException($retryAfter),
            $status === 503 => throw new PricebookUnavailableException($error ?: 'pricebook_unavailable', $retryAfter),
            default => throw new PricebookSyncException("Unexpected Pricebook Sync API response: HTTP {$status}."),
        };
    }
}
