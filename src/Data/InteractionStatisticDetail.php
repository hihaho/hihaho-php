<?php declare(strict_types=1);

namespace Hihaho\Sdk\Data;

use DateTimeImmutable;
use Hihaho\Sdk\Enums\InteractionType;
use Hihaho\Sdk\Support\Payload;

/**
 * Click statistics of one interaction.
 */
final readonly class InteractionStatisticDetail
{
    /**
     * @param  int|string|null  $typeValue  raw value; `$type` is null when the SDK does not know it yet
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public int $id,
        public string $title,
        public int|string|null $typeValue,
        public ?InteractionType $type,
        public ?string $actionType,
        public float $startTime,
        public ?float $duration,
        public ?float $endTime,
        public ?string $link,
        public ?int $totalClicks,
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
            typeValue: $p->scalar('type'),
            type: $p->enum('type', InteractionType::class),
            actionType: $p->nullableString('action_type'),
            startTime: $p->float('start_time'),
            duration: $p->nullableFloat('duration'),
            endTime: $p->nullableFloat('end_time'),
            link: $p->nullableString('link'),
            totalClicks: $p->nullableInt('total_clicks'),
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
