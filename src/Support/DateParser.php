<?php declare(strict_types=1);

namespace Hihaho\Sdk\Support;

use DateTimeImmutable;
use DateTimeZone;
use Exception;

/**
 * The v2 API returns dates in five formats; this turns each into a UTC date.
 */
final class DateParser
{
    public static function parse(mixed $value): ?DateTimeImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        $utc = new DateTimeZone('UTC');

        if (is_int($value) || (is_string($value) && ctype_digit($value))) {
            return new DateTimeImmutable("@{$value}")->setTimezone($utc);
        }

        if (! is_string($value)) {
            return null;
        }

        try {
            return new DateTimeImmutable($value, $utc)->setTimezone($utc);
        } catch (Exception) {
            return null;
        }
    }
}
