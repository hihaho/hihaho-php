<?php declare(strict_types=1);

namespace Hihaho\Sdk\Http;

use Hihaho\Sdk\Exceptions\AiTaskRefusedException;
use Hihaho\Sdk\Exceptions\AuthenticationException;
use Hihaho\Sdk\Exceptions\AuthorizationException;
use Hihaho\Sdk\Exceptions\HttpException;
use Hihaho\Sdk\Exceptions\NotFoundException;
use Hihaho\Sdk\Exceptions\PreconditionFailedException;
use Hihaho\Sdk\Exceptions\RateLimitedException;
use Hihaho\Sdk\Exceptions\ServerException;
use Hihaho\Sdk\Exceptions\UnexpectedStatusException;
use Hihaho\Sdk\Exceptions\ValidationException;
use Illuminate\Http\Client\Response;

/**
 * Maps an unsuccessful API response to the matching SDK exception.
 */
final class ErrorMapper
{
    /**
     * @param  array<array-key, mixed>  $body
     * @param  array<string, list<string>>  $headers
     */
    public function map(int $status, array $body, array $headers): HttpException
    {
        return match (true) {
            $status >= 300 && $status < 400, $status === 401 => new AuthenticationException($status, $body, $headers),
            $status === 403 => new AuthorizationException($status, $body, $headers),
            $status === 404 => new NotFoundException($status, $body, $headers),
            $status === 412 => new PreconditionFailedException($status, $body, $headers),
            $status === 422 => new ValidationException($status, $body, $headers),
            $status === 429 => new RateLimitedException($status, $body, $headers),
            $status === 400 && is_array($body['tasks'] ?? null) => new AiTaskRefusedException($status, $body, $headers),
            $status >= 500 => new ServerException($status, $body, $headers),
            default => new UnexpectedStatusException($status, $body, $headers),
        };
    }

    /**
     * @return array<string, list<string>>
     */
    public static function headersOf(Response $response): array
    {
        $headers = [];

        foreach ($response->headers() as $name => $values) {
            $headers[(string) $name] = is_array($values) ? array_values(array_filter($values, is_string(...))) : [];
        }

        return $headers;
    }
}
