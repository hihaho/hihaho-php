<?php declare(strict_types=1);

namespace Hihaho\Sdk\Exceptions;

/**
 * 429 after the SDK used up its retries, or with a `Retry-After` above 60 seconds.
 */
final class RateLimitedException extends HttpException {}
