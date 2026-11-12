<?php declare(strict_types=1);

namespace Hihaho\Sdk\Data;

use Hihaho\Sdk\Enums\InteractionType;
use Hihaho\Sdk\Support\Payload;

/**
 * Click statistics of one interaction in a period.
 */
final readonly class InteractionStatistic
{
    /**
     * @param  int|string|null  $interactionTypeValue  raw value; `$interactionType` is null when the SDK does not know it yet
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public int $id,
        public string $title,
        public int|string|null $interactionTypeValue,
        public ?InteractionType $interactionType,
        public float $startTime,
        public float $endTime,
        public bool $hasDuration,
        public string $link,
        public int $totalTimesClicked,
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
            interactionTypeValue: $p->scalar('interaction_type'),
            interactionType: $p->enum('interaction_type', InteractionType::class),
            startTime: $p->float('start_time'),
            endTime: $p->float('end_time'),
            hasDuration: $p->bool('has_duration'),
            link: $p->string('link'),
            totalTimesClicked: $p->int('total_times_clicked'),
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
