<?php declare(strict_types=1);

namespace Hihaho\Sdk\Input;

use Illuminate\Support\Arr;

/**
 * Builds import request bodies. A value that is null is left out, never sent as null: several
 * import fields reject an explicit null, and leaving a field out lets the server apply its default.
 */
final class Fields
{
    /**
     * @param  array<string, mixed>  $fields  dot paths => values
     * @param  list<array<string, mixed>>  $parts  nested arrays to merge in
     * @return array<string, mixed>
     */
    public static function build(array $fields, array $parts = []): array
    {
        $body = Arr::undot($fields);

        foreach ($parts as $part) {
            $body = array_replace_recursive($body, $part);
        }

        $compacted = [];

        foreach (self::compact($body) as $key => $value) {
            $compacted[(string) $key] = $value;
        }

        return $compacted;
    }

    /**
     * Drops nulls and arrays that end up empty.
     *
     * @param  array<array-key, mixed>  $body
     * @return array<array-key, mixed>
     */
    private static function compact(array $body): array
    {
        $result = [];

        foreach ($body as $key => $value) {
            if (is_array($value)) {
                $value = array_is_list($value) ? $value : self::compact($value);

                if ($value === []) {
                    continue;
                }
            }

            if ($value !== null) {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
