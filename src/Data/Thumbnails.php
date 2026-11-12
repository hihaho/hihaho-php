<?php declare(strict_types=1);

namespace Hihaho\Sdk\Data;

use Hihaho\Sdk\Support\Payload;

final readonly class Thumbnails
{
    /**
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public string $big,
        public string $small,
        private array $raw,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $p = new Payload($data);

        return new self(
            big: $p->string('big'),
            small: $p->string('small'),
            raw: $data,
        );
    }

    /**
     * The response exactly as the API sent it, including fields this class does not type.
     *
     * @return array<array-key, mixed>
     */
    public function raw(): array
    {
        return $this->raw;
    }
}
