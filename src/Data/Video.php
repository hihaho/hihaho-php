<?php declare(strict_types=1);

namespace Hihaho\Sdk\Data;

use DateTimeImmutable;
use Hihaho\Sdk\Enums\Embedding;
use Hihaho\Sdk\Enums\Locale;
use Hihaho\Sdk\Enums\VideoStatus;
use Hihaho\Sdk\Support\Payload;

/**
 * A video with its player and embed details.
 */
final readonly class Video
{
    /**
     * @param  int|string|null  $statusValue  raw value; `$status` is null when the SDK does not know it yet
     * @param  int|string|null  $localeValue  raw value; `$locale` is null when the SDK does not know it yet
     * @param  list<string>  $tags
     * @param  int|string|null  $enableEmbeddingValue  raw value; `$enableEmbedding` is null when the SDK does not know it yet
     * @param  list<string>|null  $allowedDomains
     * @param  list<string>|null  $containerAllowedDomains
     * @param  list<string>|null  $effectiveAllowedDomains
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public int $id,
        public string $uuid,
        public string $displayName,
        public ?string $description,
        public ?string $authorNotes,
        public int|string|null $statusValue,
        public ?VideoStatus $status,
        public int $videoContainerId,
        public Folder $videoContainer,
        public string $playerUrl,
        public string $embedUrl,
        public string $iframeElement,
        public Thumbnails $thumbnails,
        public ?int $startTime,
        public ?int $endTime,
        public ?int $duration,
        public int|string|null $localeValue,
        public ?Locale $locale,
        public array $tags,
        public ?bool $shouldAllowSeeking,
        public bool $effectiveShouldAllowSeeking,
        public int|string|null $enableEmbeddingValue,
        public ?Embedding $enableEmbedding,
        public ?array $allowedDomains,
        public ?array $containerAllowedDomains,
        public ?array $effectiveAllowedDomains,
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
            authorNotes: $p->nullableString('author_notes'),
            statusValue: $p->scalar('status'),
            status: $p->enum('status', VideoStatus::class),
            videoContainerId: $p->int('video_container_id'),
            videoContainer: Folder::fromArray($p->array('video_container')),
            playerUrl: $p->string('player_url'),
            embedUrl: $p->string('embed_url'),
            iframeElement: $p->string('iframe_element'),
            thumbnails: Thumbnails::fromArray($p->array('thumbnails')),
            startTime: $p->nullableInt('start_time'),
            endTime: $p->nullableInt('end_time'),
            duration: $p->nullableInt('duration'),
            localeValue: $p->scalar('locale'),
            locale: $p->enum('locale', Locale::class),
            tags: $p->strings('tags'),
            shouldAllowSeeking: $p->nullableBool('should_allow_seeking'),
            effectiveShouldAllowSeeking: $p->bool('effective_should_allow_seeking'),
            enableEmbeddingValue: $p->scalar('enable_embedding'),
            enableEmbedding: $p->enum('enable_embedding', Embedding::class),
            allowedDomains: $p->nullableStrings('allowed_domains'),
            containerAllowedDomains: $p->nullableStrings('container_allowed_domains'),
            effectiveAllowedDomains: $p->nullableStrings('effective_allowed_domains'),
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
