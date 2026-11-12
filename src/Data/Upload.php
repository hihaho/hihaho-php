<?php declare(strict_types=1);

namespace Hihaho\Sdk\Data;

use DateTimeImmutable;
use Hihaho\Sdk\Enums\UploadVideoStatus;
use Hihaho\Sdk\Support\Payload;

final readonly class Upload
{
    /**
     * @param  int|string|null  $statusValue  raw value; `$status` is null when the SDK does not know it yet
     * @param  list<VideoSummary>  $videos
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public int $id,
        public int|string|null $statusValue,
        public ?UploadVideoStatus $status,
        public string $statusDescription,
        public ?DateTimeImmutable $createdAt,
        public ?string $videoName,
        public ?string $platformReference,
        public User $uploadedBy,
        public Folder $videoContainer,
        public array $videos,
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
            statusDescription: $p->string('status_description'),
            createdAt: $p->date('created_at'),
            videoName: $p->nullableString('video_name'),
            platformReference: $p->nullableString('platform_reference'),
            uploadedBy: User::fromArray($p->array('uploaded_by')),
            videoContainer: Folder::fromArray($p->array('video_container')),
            videos: $p->list('videos', VideoSummary::fromArray(...)),
            raw: $data,
        );
    }

    /**
     * The video platform has processed the video.
     */
    public function isComplete(): bool
    {
        return $this->status?->isComplete() === true;
    }

    /**
     * The upload failed, or the video platform could not process it. Register a new upload.
     */
    public function isFailed(): bool
    {
        return $this->status?->isFailed() === true;
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
