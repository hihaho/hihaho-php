<?php declare(strict_types=1);

namespace Hihaho\Sdk\Exceptions;

abstract class HttpException extends HihahoException
{
    /**
     * @param  array<array-key, mixed>  $body  the decoded JSON body; empty when the body is not JSON
     * @param  array<string, list<string>>  $headers
     */
    public function __construct(
        private readonly int $status,
        private readonly array $body = [],
        private readonly array $headers = [],
        ?string $message = null,
    ) {
        parent::__construct($message ?? $this->messageFrom($status, $body), $status);
    }

    public function status(): int
    {
        return $this->status;
    }

    /**
     * @return array<array-key, mixed>
     */
    public function body(): array
    {
        return $this->body;
    }

    /**
     * @return array<string, list<string>>
     */
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * @param  array<array-key, mixed>  $body
     */
    private function messageFrom(int $status, array $body): string
    {
        $message = $body['message'] ?? null;

        return is_string($message) && $message !== ''
            ? $message
            : "The hihaho API returned HTTP {$status}.";
    }
}
