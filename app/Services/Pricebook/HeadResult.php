<?php

namespace App\Services\Pricebook;

final readonly class HeadResult
{
    private function __construct(
        public bool $notModified,
        public ?int $revision = null,
        public ?string $etag = null,
    ) {
    }

    public static function notModified(): self
    {
        return new self(notModified: true);
    }

    public static function ok(int $revision, ?string $etag): self
    {
        return new self(notModified: false, revision: $revision, etag: $etag);
    }
}
