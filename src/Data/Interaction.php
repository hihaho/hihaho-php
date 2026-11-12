<?php declare(strict_types=1);

namespace Hihaho\Sdk\Data;

use DateTimeImmutable;
use Hihaho\Sdk\Enums\InteractionType;
use Hihaho\Sdk\Enums\Locale;
use Hihaho\Sdk\Support\Payload;

/**
 * An interaction as stored. `action`, `style` and `attributes` are the internal shape, so they stay raw arrays. The import shape is in the `Import` namespace.
 */
final readonly class Interaction
{
    /**
     * @param  int|string|null  $typeValue  raw value; `$type` is null when the SDK does not know it yet
     * @param  int|string|null  $localeValue  raw value; `$locale` is null when the SDK does not know it yet
     * @param  array<array-key, mixed>|null  $action
     * @param  array<array-key, mixed>|null  $style
     * @param  array<array-key, mixed>|null  $attributes
     * @param  array<array-key, mixed>  $chapters
     * @param  array<array-key, mixed>  $summarySections
     * @param  array<array-key, mixed>  $glossaryTerms
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public int $id,
        public int $videoId,
        public int|string|null $typeValue,
        public ?InteractionType $type,
        public string $title,
        public string $text,
        public float $time,
        public float $duration,
        public ?float $endTime,
        public int|string|null $localeValue,
        public ?Locale $locale,
        public bool $isPopup,
        public bool $isAiGenerated,
        public ?string $image,
        public ?string $imageAlt,
        public ?string $sound,
        public ?int $interactionGroupId,
        public ?array $action,
        public ?array $style,
        public ?array $attributes,
        public array $chapters,
        public array $summarySections,
        public array $glossaryTerms,
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
            videoId: $p->int('video_id'),
            typeValue: $p->scalar('type'),
            type: $p->enum('type', InteractionType::class),
            title: $p->string('title'),
            text: $p->string('text'),
            time: $p->float('time'),
            duration: $p->float('duration'),
            endTime: $p->nullableFloat('end_time'),
            localeValue: $p->scalar('locale'),
            locale: $p->enum('locale', Locale::class),
            isPopup: $p->bool('is_popup'),
            isAiGenerated: $p->bool('is_ai_generated'),
            image: $p->nullableString('image'),
            imageAlt: $p->nullableString('image_alt'),
            sound: $p->nullableString('sound'),
            interactionGroupId: $p->nullableInt('interaction_group_id'),
            action: $p->nullableArray('action'),
            style: $p->nullableArray('style'),
            attributes: $p->nullableArray('attributes'),
            chapters: $p->array('chapters'),
            summarySections: $p->array('summary_sections'),
            glossaryTerms: $p->array('glossary_terms'),
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
