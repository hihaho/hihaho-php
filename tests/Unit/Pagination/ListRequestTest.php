<?php declare(strict_types=1);

namespace Hihaho\Sdk\Tests\Unit\Pagination;

use Hihaho\Sdk\Http\Connector;
use Hihaho\Sdk\Pagination\BareList;
use Hihaho\Sdk\Pagination\ListRequest;
use Hihaho\Sdk\Pagination\PagePaginator;
use Hihaho\Sdk\Pagination\PaginationStrategy;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ListRequestTest extends TestCase
{
    private Factory $http;

    protected function setUp(): void
    {
        parent::setUp();

        $this->http = new Factory();
    }

    #[Test]
    public function page_two_keeps_the_callers_filters(): void
    {
        $this->http->fakeSequence()
            ->push($this->pageBody([1, 2], current: 1, last: 2, next: 'https://api.hihaho.com/v2/video?page=2'))
            ->push($this->pageBody([3], current: 2, last: 2));

        $ids = $this->listRequest(new PagePaginator(), ['status' => 'published', 'q' => 'safety'])->all()->all();

        $this->assertSame([1, 2, 3], $ids);
        $this->http->assertSent(fn (Request $request): bool => $request->url() === 'https://api.hihaho.com/v2/video?status=published&q=safety&page=2');
    }

    #[Test]
    public function foreach_walks_every_page(): void
    {
        $this->http->fakeSequence()
            ->push($this->pageBody([1, 2], current: 1, last: 2))
            ->push($this->pageBody([3], current: 2, last: 2));
        $ids = [];

        foreach ($this->listRequest(new PagePaginator()) as $id) {
            $ids[] = $id;
        }

        $this->assertSame([1, 2, 3], $ids);
    }

    #[Test]
    public function a_repeated_row_is_yielded_once(): void
    {
        $this->http->fakeSequence()
            ->push($this->pageBody([1, 2], current: 1, last: 2))
            ->push($this->pageBody([2, 3], current: 2, last: 2));

        $this->assertSame([1, 2, 3], $this->listRequest(new PagePaginator())->all()->all());
    }

    #[Test]
    public function rows_without_an_id_are_all_yielded(): void
    {
        $this->http->fakeSequence()
            ->push(['data' => [['name' => 'a'], ['name' => 'b']], 'meta' => ['last_page' => 2]])
            ->push(['data' => [['name' => 'c']], 'meta' => ['last_page' => 2]]);

        $this->assertSame([0, 0, 0], $this->listRequest(new PagePaginator())->all()->all());
    }

    #[Test]
    public function a_page_of_only_repeated_rows_does_not_stop_the_walk(): void
    {
        $this->http->fakeSequence()
            ->push($this->pageBody([1, 2], current: 1, last: 3))
            ->push($this->pageBody([1, 2], current: 2, last: 3))
            ->push($this->pageBody([3], current: 3, last: 3));

        $this->assertSame([1, 2, 3], $this->listRequest(new PagePaginator())->all()->all());
        $this->http->assertSentCount(3);
    }

    #[Test]
    public function without_a_last_page_the_next_link_continues_the_walk(): void
    {
        $this->http->fakeSequence()
            ->push(['data' => [['id' => 1]], 'links' => ['next' => 'https://api.hihaho.com/v2/video?page=2'], 'meta' => ['current_page' => 1]])
            ->push(['data' => [['id' => 2]], 'links' => ['next' => null], 'meta' => ['current_page' => 2]]);

        $this->assertSame([1, 2], $this->listRequest(new PagePaginator())->all()->all());
        $this->http->assertSentCount(2);
    }

    #[Test]
    public function an_empty_last_page_ends_the_walk(): void
    {
        $this->http->fakeSequence()
            ->push($this->pageBody([1], current: 1, last: 2))
            ->push($this->pageBody([], current: 2, last: 2));

        $this->assertSame([1], $this->listRequest(new PagePaginator())->all()->all());
    }

    #[Test]
    public function the_last_page_has_no_next_page(): void
    {
        $this->http->fake(['*' => Factory::response($this->pageBody([1], current: 1, last: 1))]);

        $page = $this->listRequest(new PagePaginator())->page();

        $this->assertFalse($page->hasMore());
        $this->assertNull($page->nextPage());
    }

    #[Test]
    public function breaking_early_fetches_no_more_pages(): void
    {
        $this->http->fakeSequence()
            ->push($this->pageBody([1, 2], current: 1, last: 5))
            ->push($this->pageBody([3, 4], current: 2, last: 5));

        $first = $this->listRequest(new PagePaginator())->all()->first();

        $this->assertSame(1, $first);
        $this->http->assertSentCount(1);
    }

    #[Test]
    public function a_bare_list_is_one_page(): void
    {
        $this->http->fake(['*' => Factory::response(['data' => [['id' => 7], ['id' => 8]]])]);

        $page = $this->listRequest(new BareList())->page();

        $this->assertSame([7, 8], $page->items);
        $this->assertFalse($page->hasMore());
        $this->http->assertSent(fn (Request $request): bool => ! str_contains($request->url(), 'page='));
    }

    #[Test]
    public function an_unwrapped_bare_list_is_read_too(): void
    {
        $this->http->fake(['*' => Factory::response([['id' => 7]])]);

        $this->assertSame([7], $this->listRequest(new BareList())->all()->all());
    }

    /**
     * @param  array<string, mixed>  $query
     * @return ListRequest<int>
     */
    private function listRequest(PaginationStrategy $strategy, array $query = []): ListRequest
    {
        return new ListRequest(
            connector: new Connector(http: $this->http, token: 'token'),
            strategy: $strategy,
            path: 'v2/video',
            query: $query,
            map: fn (array $row): int => is_int($row['id'] ?? null) ? $row['id'] : 0,
            keyOf: fn (int $id): int => $id,
        );
    }

    /**
     * @param  list<int>  $ids
     * @return array<string, mixed>
     */
    private function pageBody(array $ids, int $current, int $last, ?string $next = null): array
    {
        return [
            'data' => array_map(fn (int $id): array => ['id' => $id], $ids),
            'links' => ['next' => $next],
            'meta' => ['current_page' => $current, 'last_page' => $last, 'total' => 99],
        ];
    }
}
