<?php declare(strict_types=1);

namespace Hihaho\Sdk\Tests\Unit\Resources;

use Hihaho\Sdk\Hihaho;
use Hihaho\Sdk\Http\RetryPolicy;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Request;
use PHPUnit\Framework\TestCase;

abstract class ResourceTestCase extends TestCase
{
    protected Factory $http;

    protected Hihaho $hihaho;

    protected function setUp(): void
    {
        parent::setUp();

        $this->http = new Factory();
        $this->hihaho = new Hihaho(token: 'token', http: $this->http, retryPolicy: RetryPolicy::disabled());
    }

    /**
     * @param  array<array-key, mixed>|string  $body
     */
    protected function respond(array|string $body, int $status = 200): void
    {
        $this->http->fake(['*' => Factory::response($body, $status)]);
    }

    protected function assertRequested(string $method, string $pathAndQuery): void
    {
        $this->http->assertSent(fn (Request $request): bool => $request->method() === $method
            && $request->url() === 'https://api.hihaho.com/' . $pathAndQuery);
    }

    /**
     * @return array<string, mixed>
     */
    protected static function folderRow(int $id = 7): array
    {
        return ['id' => $id, 'name' => 'Training', 'created_at' => '2026-10-15 14:09:51', 'parent_id' => null, 'external_identifier' => 'team-1'];
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, mixed>
     */
    protected static function paginated(array $rows, int $page = 1, int $last = 1): array
    {
        return [
            'data' => $rows,
            'links' => ['first' => null, 'last' => null, 'prev' => null, 'next' => null],
            'meta' => ['current_page' => $page, 'from' => 1, 'last_page' => $last, 'links' => [], 'path' => '', 'per_page' => 250, 'to' => 1, 'total' => count($rows)],
        ];
    }
}
