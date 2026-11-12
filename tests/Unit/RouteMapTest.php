<?php declare(strict_types=1);

namespace Hihaho\Sdk\Tests\Unit;

use Hihaho\Sdk\Hihaho;
use Hihaho\Sdk\Support\RouteMap;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;
use ReflectionNamedType;

/**
 * `tests/Fixtures/v2-routes.json` is the API's current route list.
 */
final class RouteMapTest extends TestCase
{
    #[Test]
    public function every_server_route_has_an_sdk_method_or_an_exclusion(): void
    {
        $serverRoutes = array_keys($this->serverRoutes());
        $covered = [...array_keys(RouteMap::ROUTES), ...array_keys(RouteMap::EXCLUDED)];

        $this->assertSame([], array_values(array_diff($serverRoutes, $covered)), 'Routes without an SDK method: add them to RouteMap.');
        $this->assertSame([], array_values(array_diff($covered, $serverRoutes)), 'RouteMap names routes the server does not have.');
    }

    #[Test]
    public function every_mapped_route_has_the_server_method_and_uri(): void
    {
        $serverRoutes = $this->serverRoutes();

        foreach (RouteMap::ROUTES as $route => [$method, $uri]) {
            $this->assertSame($serverRoutes[$route] ?? null, ['method' => $method, 'uri' => $uri], "{$route}: the server route changed; update RouteMap and the SDK method.");
        }
    }

    #[Test]
    public function every_mapped_method_exists(): void
    {
        foreach (RouteMap::ROUTES as $route => [, , $chain]) {
            $class = Hihaho::class;

            foreach ($chain as $method) {
                $this->assertTrue(method_exists($class, $method), "{$route}: {$class}::{$method}() does not exist.");

                $returnType = new ReflectionMethod($class, $method)->getReturnType();
                $class = $returnType instanceof ReflectionNamedType ? $returnType->getName() : '';
            }
        }
    }

    /**
     * @return array<array{method: mixed, uri: mixed}>
     */
    private function serverRoutes(): array
    {
        $routes = json_decode((string) file_get_contents(__DIR__ . '/../Fixtures/v2-routes.json'), true, flags: JSON_THROW_ON_ERROR);
        $this->assertIsArray($routes);

        return array_map(
            fn (mixed $route): array => ['method' => is_array($route) ? $route['method'] ?? null : null, 'uri' => is_array($route) ? $route['uri'] ?? null : null],
            array_column($routes, null, 'name'),
        );
    }
}
