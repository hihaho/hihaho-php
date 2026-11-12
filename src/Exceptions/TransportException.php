<?php declare(strict_types=1);

namespace Hihaho\Sdk\Exceptions;

/**
 * The request never got a response: a connection failure or a timeout. The cause is not kept as
 * the previous exception, because it holds the request with the bearer token.
 */
final class TransportException extends HihahoException
{
    public static function from(string $message): self
    {
        return new self($message);
    }
}
