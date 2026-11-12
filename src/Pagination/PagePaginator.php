<?php declare(strict_types=1);

namespace Hihaho\Sdk\Pagination;

use Closure;
use Hihaho\Sdk\Exceptions\InvalidResponseException;
use Hihaho\Sdk\Http\Connector;

/**
 * Laravel page-number paging. The next request is rebuilt from the caller's filters and the
 * next page number.
 */
final class PagePaginator implements PaginationStrategy
{
    public function fetch(Connector $connector, ListRequest $request, string $path, array $query, Closure $map, int $page): Page
    {
        $response = $connector->get($path, [...$query, 'page' => $page]);
        $body = $connector->decode($response);
        $rows = $body['data'] ?? null;

        if (! is_array($rows)) {
            throw InvalidResponseException::withoutData($response);
        }

        $meta = is_array($body['meta'] ?? null) ? $body['meta'] : [];

        return new Page(
            items: array_values(array_map($map, array_filter($rows, is_array(...)))),
            number: $page,
            hasMore: $this->hasMore($meta, $page, $body),
            meta: $meta,
            request: $request,
        );
    }

    /**
     * @param  array<array-key, mixed>  $meta
     * @param  array<array-key, mixed>  $body
     */
    private function hasMore(array $meta, int $page, array $body): bool
    {
        $lastPage = $meta['last_page'] ?? null;

        if (is_int($lastPage)) {
            return $page < $lastPage;
        }

        $links = $body['links'] ?? null;

        return is_array($links) && ($links['next'] ?? null) !== null;
    }
}
