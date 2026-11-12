<?php declare(strict_types=1);

namespace Hihaho\Sdk\Resources;

use Closure;
use Hihaho\Sdk\Exceptions\InvalidResponseException;
use Hihaho\Sdk\Http\Connector;
use Hihaho\Sdk\Pagination\BareList;
use Hihaho\Sdk\Pagination\ListRequest;
use Hihaho\Sdk\Pagination\PagePaginator;
use Illuminate\Http\Client\Response;
use InvalidArgumentException;

abstract readonly class Resource
{
    public function __construct(protected Connector $connector) {}

    /**
     * @template T
     *
     * @param  array<string, mixed>  $query
     * @param  Closure(array<array-key, mixed>): T  $map
     * @param  Closure(T): (int|string)  $keyOf
     * @return ListRequest<T>
     */
    protected function paged(string $path, array $query, Closure $map, Closure $keyOf): ListRequest
    {
        return new ListRequest($this->connector, new PagePaginator(), $path, $query, $map, $keyOf);
    }

    /**
     * @template T
     *
     * @param  array<string, mixed>  $query
     * @param  Closure(array<array-key, mixed>): T  $map
     * @param  Closure(T): (int|string)  $keyOf
     * @return ListRequest<T>
     */
    protected function bare(string $path, array $query, Closure $map, Closure $keyOf): ListRequest
    {
        return new ListRequest($this->connector, new BareList(), $path, $query, $map, $keyOf);
    }

    /**
     * The API rejects a batch above its maximum; checking first keeps one call all-or-nothing.
     *
     * @param  list<int>  $ids
     * @return array{id: list<int>}
     */
    protected function idBatch(array $ids, int $max): array
    {
        if ($ids === [] || count($ids) > $max) {
            throw new InvalidArgumentException("Pass between 1 and {$max} ids; split a larger batch into several calls.");
        }

        return ['id' => array_values($ids)];
    }

    /**
     * @return array<array-key, mixed>
     */
    protected function data(Response $response): array
    {
        $data = $this->connector->decode($response)['data'] ?? null;

        if (! is_array($data)) {
            throw InvalidResponseException::withoutData($response);
        }

        return $data;
    }

    /**
     * @return list<int>
     */
    protected function ids(Response $response): array
    {
        $ids = $this->connector->decode($response)['ids'] ?? null;

        if (! is_array($ids) || ! array_is_list($ids)) {
            throw InvalidResponseException::withoutIds($response);
        }

        return array_map(fn (mixed $id): int => is_int($id) ? $id : throw InvalidResponseException::withoutIds($response), $ids);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<array-key, mixed>
     */
    protected function getData(string $path, array $query = []): array
    {
        return $this->data($this->connector->get($path, $query));
    }

    /**
     * A response that is not wrapped in `data`.
     *
     * @param  array<string, mixed>  $query
     * @return array<array-key, mixed>
     */
    protected function getBare(string $path, array $query = []): array
    {
        return $this->connector->decode($this->connector->get($path, $query));
    }
}
