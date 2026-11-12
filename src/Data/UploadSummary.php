<?php declare(strict_types=1);

namespace Hihaho\Sdk\Data;

use DateTimeImmutable;
use Hihaho\Sdk\Enums\UploadVideoStatus;
use Hihaho\Sdk\Support\Payload;

/**
 * A video upload in a list.
 */
final readonly class UploadSummary
{
    /**
     * @param  int|string|null  $statusValue  raw value; `$status` is null when the SDK does not know it yet
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public int $id,
        public int|string|null $statusValue,
        public ?UploadVideoStatus $status,
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
            id: $p->int('id'),
            statusValue: $p->scalar('status'),
            status: $p->enum('status', UploadVideoStatus::class),
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
