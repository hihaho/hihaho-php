<?php declare(strict_types=1);

namespace Hihaho\Sdk\Http;

final readonly class RetryPolicy
{
    public function __construct(
        public int $maxRetries = 3,
        public bool $retryRateLimited = true,
        public bool $retryServerErrorsOnGet = true,
        public int $initialBackoffSeconds = 1,
    ) {}

    public static function disabled(): self
    {
        return new self(maxRetries: 0, retryRateLimited: false, retryServerErrorsOnGet: false);
    }
}
