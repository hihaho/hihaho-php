<?php declare(strict_types=1);

namespace Hihaho\Sdk\Data;

use DateTimeImmutable;
use Hihaho\Sdk\Enums\VideoStatus;
use Hihaho\Sdk\Support\Payload;

/**
 * A video in a list.
 */
final readonly class VideoSummary
{
    /**
     * @param  int|string|null  $statusValue  raw value; `$status` is null when the SDK does not know it yet
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public int $id,
        public string $uuid,
        public string $displayName,
        public ?string $description,
        public int|string|null $statusValue,
        public ?VideoStatus $status,
        public int $videoContainerId,
        public Folder $videoContainer,
        public ?DateTimeImmutable $createdAt,
        public ?DateTimeImmutable $updatedAt,
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
            uuid: $p->string('uuid'),
            displayName: $p->string('display_name'),
            description: $p->nullableString('description'),
            statusValue: $p->scalar('status'),
            status: $p->enum('status', VideoStatus::class),
            videoContainerId: $p->int('video_container_id'),
            videoContainer: Folder::fromArray($p->array('video_container')),
            createdAt: $p->date('created_at'),
            updatedAt: $p->date('updated_at'),
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
