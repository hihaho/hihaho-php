<?php declare(strict_types=1);

namespace Hihaho\Sdk\Pagination;

/**
 * One page of a list. `total` and `last_page` are in `meta` but are not a stable contract.
 *
 * @template T
 */
final readonly class Page
{
    /**
     * @param  list<T>  $items
     * @param  array<array-key, mixed>  $meta
     * @param  ListRequest<T>|null  $request
     */
    public function __construct(
        public array $items,
        public int $number,
        private bool $hasMore,
        public array $meta = [],
        private ?ListRequest $request = null,
    ) {}

    public function hasMore(): bool
    {
        return $this->hasMore;
    }

    /**
     * @return Page<T>|null
     */
    public function nextPage(): ?Page
    {
        if (! $this->hasMore || ! $this->request instanceof ListRequest) {
            return null;
        }

        return $this->request->page($this->number + 1);
    }
}
