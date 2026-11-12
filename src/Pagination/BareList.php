<?php declare(strict_types=1);

namespace Hihaho\Sdk\Pagination;

use Closure;
use Hihaho\Sdk\Exceptions\InvalidResponseException;
use Hihaho\Sdk\Http\Connector;

/**
 * An endpoint that returns every row at once, wrapped in `data` or bare.
 */
final class BareList implements PaginationStrategy
{
    public function fetch(Connector $connector, ListRequest $request, string $path, array $query, Closure $map, int $page): Page
    {
        $response = $connector->get($path, $query);
        $body = $connector->decode($response);
        $rows = array_is_list($body) ? $body : $body['data'] ?? null;

        if (! is_array($rows)) {
            throw InvalidResponseException::withoutData($response);
        }

        return new Page(
            items: array_values(array_map($map, array_filter($rows, is_array(...)))),
            number: 1,
            hasMore: false,
            request: $request,
        );
    }
}
