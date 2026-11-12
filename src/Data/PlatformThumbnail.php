<?php declare(strict_types=1);

namespace Hihaho\Sdk\Data;

use DateTimeImmutable;
use Hihaho\Sdk\Support\Payload;

final readonly class PlatformThumbnail
{
    /**
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public string $id,
        public string $status,
        public string $thumbnailType,
        public string $deliveryUrl,
        public bool $isPoster,
        public ?DateTimeImmutable $createdAt,
        private array $raw,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $p = new Payload($data);

        return new self(
            id: $p->string('id'),
            status: $p->string('status'),
            thumbnailType: $p->string('thumbnail_type'),
            deliveryUrl: $p->string('delivery_url'),
            isPoster: $p->bool('is_poster'),
            createdAt: $p->date('created_at'),
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
