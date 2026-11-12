<?php declare(strict_types=1);

namespace Hihaho\Sdk\Pagination;

use Closure;
use Hihaho\Sdk\Http\Connector;

interface PaginationStrategy
{
    /**
     * @template T
     *
     * @param  ListRequest<T>  $request  the list the returned page continues
     * @param  array<string, mixed>  $query
     * @param  Closure(array<array-key, mixed>): T  $map
     * @return Page<T>
     */
    public function fetch(Connector $connector, ListRequest $request, string $path, array $query, Closure $map, int $page): Page;
}
