<?php declare(strict_types=1);

namespace Hihaho\Sdk\Tests\Unit\Support;

use BackedEnum;
use Hihaho\Sdk\Enums\AiTaskStatus;
use Hihaho\Sdk\Enums\UploadVideoStatus;
use Hihaho\Sdk\Support\Payload;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class PayloadTest extends TestCase
{
    /**
     * @param  class-string<BackedEnum>  $enum
     */
    #[Test]
    #[DataProvider('enumProvider')]
    public function an_enum_value_of_the_other_scalar_type_is_read_without_a_type_error(mixed $value, string $enum, ?BackedEnum $expected): void
    {
        $this->assertSame($expected, new Payload(['status' => $value])->enum('status', $enum));
    }

    /**
     * @return iterable<string, array{mixed, class-string<BackedEnum>, BackedEnum|null}>
     */
    public static function enumProvider(): iterable
    {
        yield 'numeric string for an int enum' => ['6', UploadVideoStatus::class, UploadVideoStatus::Ready];
        yield 'text for an int enum' => ['abc', UploadVideoStatus::class, null];
        yield 'int for a string enum' => [1, NumericCode::class, NumericCode::One];
        yield 'unknown int for a string enum' => [2, NumericCode::class, null];
        yield 'string for a string enum' => [AiTaskStatus::cases()[0]->value, AiTaskStatus::class, AiTaskStatus::cases()[0]];
    }
}

enum NumericCode: string
{
    case One = '1';
}
