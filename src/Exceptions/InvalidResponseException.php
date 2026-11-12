<?php declare(strict_types=1);

namespace Hihaho\Sdk\Exceptions;

use Hihaho\Sdk\Http\ErrorMapper;
use Illuminate\Http\Client\Response;

/**
 * A successful status with a body that is not valid JSON, or without the `data` or `ids` it needs.
 */
final class InvalidResponseException extends HttpException
{
    public static function withoutData(Response $response): self
    {
        return new self($response->status(), headers: ErrorMapper::headersOf($response), message: 'The hihaho API returned a body without a `data` object or list.');
    }

    public static function withoutIds(Response $response): self
    {
        return new self($response->status(), headers: ErrorMapper::headersOf($response), message: 'The hihaho API returned a body without an `ids` list.');
    }

    public static function notJson(Response $response): self
    {
        return new self($response->status(), headers: ErrorMapper::headersOf($response), message: 'The hihaho API returned a body that is not a JSON object or array.');
    }
}
