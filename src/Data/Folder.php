<?php declare(strict_types=1);

namespace Hihaho\Sdk\Data;

use DateTimeImmutable;
use Hihaho\Sdk\Support\Payload;

/**
 * A folder (video container).
 */
final readonly class Folder
{
    /**
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public int $id,
        public string $name,
        public ?DateTimeImmutable $createdAt,
        public ?int $parentId,
        public ?string $externalIdentifier,
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
            createdAt: $p->date('created_at'),
            parentId: $p->nullableInt('parent_id'),
            externalIdentifier: $p->nullableString('external_identifier'),
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
