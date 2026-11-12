<?php declare(strict_types=1);

namespace Hihaho\Sdk\Support;

use BackedEnum;
use Closure;
use DateTimeImmutable;
use ReflectionEnum;

/**
 * Typed reads from one decoded API object. Missing or mistyped values read as null (or the
 * empty default), so a field the server drops or renames never throws in a read model.
 */
final readonly class Payload
{
    /**
     * @param  array<array-key, mixed>  $data
     */
    public function __construct(public array $data) {}

    public function int(string $key, int $default = 0): int
    {
        return $this->nullableInt($key) ?? $default;
    }

    public function nullableInt(string $key): ?int
    {
        $value = $this->data[$key] ?? null;

        if (is_int($value)) {
            return $value;
        }

        return is_string($value) && ctype_digit($value) ? (int) $value : null;
    }

    public function float(string $key, float $default = 0.0): float
    {
        return $this->nullableFloat($key) ?? $default;
    }

    /**
     * JSON drops the fraction of a whole float (`100.0` arrives as `100`), so an int is accepted too.
     */
    public function nullableFloat(string $key): ?float
    {
        $value = $this->data[$key] ?? null;

        return is_int($value) || is_float($value) ? (float) $value : null;
    }

    public function string(string $key, string $default = ''): string
    {
        return $this->nullableString($key) ?? $default;
    }

    public function nullableString(string $key): ?string
    {
        $value = $this->data[$key] ?? null;

        return is_string($value) ? $value : null;
    }

    public function bool(string $key, bool $default = false): bool
    {
        return $this->nullableBool($key) ?? $default;
    }

    public function nullableBool(string $key): ?bool
    {
        $value = $this->data[$key] ?? null;

        return is_bool($value) ? $value : null;
    }

    public function scalar(string $key): int|string|null
    {
        $value = $this->data[$key] ?? null;

        return is_int($value) || is_string($value) ? $value : null;
    }

    public function date(string $key): ?DateTimeImmutable
    {
        return DateParser::parse($this->data[$key] ?? null);
    }

    /**
     * @template TEnum of BackedEnum
     *
     * @param  class-string<TEnum>  $enum
     * @return TEnum|null
     */
    public function enum(string $key, string $enum): ?BackedEnum
    {
        $value = $this->data[$key] ?? null;

        if ((string) new ReflectionEnum($enum)->getBackingType() === 'int') {
            $value = is_string($value) ? filter_var($value, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE) : $value;

            return is_int($value) ? $enum::tryFrom($value) : null;
        }

        if (is_int($value)) {
            $value = (string) $value;
        }

        return is_string($value) ? $enum::tryFrom($value) : null;
    }

    /**
     * @return array<array-key, mixed>
     */
    public function array(string $key): array
    {
        $value = $this->data[$key] ?? null;

        return is_array($value) ? $value : [];
    }

    /**
     * @return array<array-key, mixed>|null
     */
    public function nullableArray(string $key): ?array
    {
        $value = $this->data[$key] ?? null;

        return is_array($value) ? $value : null;
    }

    public function object(string $key): self
    {
        return new self($this->array($key));
    }

    /**
     * @template T
     *
     * @param  Closure(array<array-key, mixed>): T  $map
     * @return list<T>
     */
    public function list(string $key, Closure $map): array
    {
        return array_values(array_map($map, array_filter($this->array($key), is_array(...))));
    }

    /**
     * @return list<string>
     */
    public function strings(string $key): array
    {
        return array_values(array_filter($this->array($key), is_string(...)));
    }

    /**
     * @return list<string>|null
     */
    public function nullableStrings(string $key): ?array
    {
        return $this->nullableArray($key) === null ? null : $this->strings($key);
    }
}
