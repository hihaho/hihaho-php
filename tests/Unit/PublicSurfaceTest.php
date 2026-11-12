<?php declare(strict_types=1);

namespace Hihaho\Sdk\Tests\Unit;

use Hihaho\Sdk\Hihaho;
use Hihaho\Sdk\Pagination\ListRequest;
use Hihaho\Sdk\Resources\VideoScope;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionProperty;

final class PublicSurfaceTest extends TestCase
{
    /**
     * @param  class-string  $class
     */
    #[Test]
    #[DataProvider('classProvider')]
    public function internal_state_is_not_public(string $class): void
    {
        $public = array_map(fn (ReflectionProperty $property): string => $property->getName(), new ReflectionClass($class)->getProperties(ReflectionProperty::IS_PUBLIC));

        $this->assertSame([], $public);
    }

    /**
     * @return iterable<string, array{class-string}>
     */
    public static function classProvider(): iterable
    {
        yield 'Hihaho' => [Hihaho::class];
        yield 'ListRequest' => [ListRequest::class];
        yield 'VideoScope' => [VideoScope::class];
    }
}
