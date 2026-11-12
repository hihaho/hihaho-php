<?php declare(strict_types=1);

namespace Hihaho\Sdk\Pagination;

use Closure;
use Hihaho\Sdk\Http\Connector;
use Illuminate\Support\LazyCollection;
use IteratorAggregate;
use Override;
use Traversable;

/**
 * A list call: the path, the caller's filters, and how to turn one row into a read model.
 *
 * @template T
 *
 * @implements IteratorAggregate<int, T>
 */
final readonly class ListRequest implements IteratorAggregate
{
    /**
     * @param  array<string, mixed>  $query
     * @param  Closure(array<array-key, mixed>): T  $map
     * @param  Closure(T): (int|string)  $keyOf
     */
    public function __construct(
        private Connector $connector,
        private PaginationStrategy $strategy,
        private string $path,
        private array $query,
        private Closure $map,
        private Closure $keyOf,
    ) {}

    /**
     * @return Page<T>
     */
    public function page(int $page = 1): Page
    {
        return $this->strategy->fetch($this->connector, $this, $this->path, $this->query, $this->map, $page);
    }

    /**
     * Walks every page lazily. A row that a later page repeats is yielded once; rows without an id
     * are all yielded.
     *
     * @return LazyCollection<int, T>
     */
    public function all(): LazyCollection
    {
        return LazyCollection::make(function () {
            $seen = [];
            $page = $this->page();

            while (true) {
                foreach ($page->items as $item) {
                    $key = ($this->keyOf)($item);

                    if ($key !== 0 && $key !== '') {
                        if (isset($seen[$key])) {
                            continue;
                        }

                        $seen[$key] = true;
                    }

                    yield $item;
                }

                $next = $page->nextPage();

                if (! $next instanceof Page) {
                    return;
                }

                $page = $next;
            }
        });
    }

    /**
     * @return Traversable<int, T>
     */
    #[Override]
    public function getIterator(): Traversable
    {
        return $this->all()->getIterator();
    }
}
