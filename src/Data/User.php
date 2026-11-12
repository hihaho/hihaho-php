<?php declare(strict_types=1);

namespace Hihaho\Sdk\Data;

use Hihaho\Sdk\Support\Payload;

final readonly class User
{
    /**
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public int $id,
        public string $name,
        private array $raw,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $p = new Payload($data);

        return new self(
            id: $p->int('id'),
            name: $p->string('name'),
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
