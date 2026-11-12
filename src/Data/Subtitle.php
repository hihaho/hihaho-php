<?php declare(strict_types=1);

namespace Hihaho\Sdk\Data;

use DateTimeImmutable;
use Hihaho\Sdk\Enums\SubtitleType;
use Hihaho\Sdk\Support\Payload;

/**
 * `source` is a translated label, not a code.
 */
final readonly class Subtitle
{
    /**
     * @param  int|string|null  $typeValue  raw value; `$type` is null when the SDK does not know it yet
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public int $id,
        public string $title,
        public ?string $label,
        public string $language,
        public string $content,
        public int|string|null $typeValue,
        public ?SubtitleType $type,
        public int $delayMs,
        public bool $isRtl,
        public ?string $source,
        public ?string $sourceReference,
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
            title: $p->string('title'),
            label: $p->nullableString('label'),
            language: $p->string('language'),
            content: $p->string('content'),
            typeValue: $p->scalar('type'),
            type: $p->enum('type', SubtitleType::class),
            delayMs: $p->int('delay_ms'),
            isRtl: $p->bool('is_rtl'),
            source: $p->nullableString('source'),
            sourceReference: $p->nullableString('source_reference'),
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
