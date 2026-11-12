<?php declare(strict_types=1);

namespace Hihaho\Sdk\Tests\Unit\Support;

use DateTimeImmutable;
use Hihaho\Sdk\Support\DateParser;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class DateParserTest extends TestCase
{
    #[Test]
    #[DataProvider('formatProvider')]
    public function it_parses_every_format_the_api_returns(mixed $value, string $expected): void
    {
        $date = DateParser::parse($value);

        $this->assertInstanceOf(DateTimeImmutable::class, $date);
        $this->assertSame('UTC', $date->getTimezone()->getName());
        $this->assertSame($expected, $date->format('Y-m-d H:i:s.v'));
    }

    /**
     * @return iterable<string, array{mixed, string}>
     */
    public static function formatProvider(): iterable
    {
        yield 'iso 8601 with microseconds' => ['2026-10-15T14:09:51.370347Z', '2026-10-15 14:09:51.370'];
        yield 'date and time' => ['2026-10-15 14:09:51', '2026-10-15 14:09:51.000'];
        yield 'unix timestamp' => [1792073391, '2026-10-15 14:09:51.000'];
        yield 'unix timestamp as string' => ['1792073391', '2026-10-15 14:09:51.000'];
        yield 'date and time with milliseconds' => ['2026-10-15 14:09:51.370', '2026-10-15 14:09:51.370'];
        yield 'iso 8601 with offset' => ['2026-10-15T16:09:51+02:00', '2026-10-15 14:09:51.000'];
    }

    #[Test]
    #[DataProvider('emptyProvider')]
    public function it_returns_null_for_empty_or_unparseable_values(mixed $value): void
    {
        $this->assertNull(DateParser::parse($value));
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function emptyProvider(): iterable
    {
        yield 'null' => [null];
        yield 'empty string' => [''];
        yield 'garbage' => ['not a date'];
        yield 'array' => [[]];
    }
}
