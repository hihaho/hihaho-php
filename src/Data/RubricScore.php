<?php declare(strict_types=1);

namespace Hihaho\Sdk\Data;

use Hihaho\Sdk\Enums\RubricMatchStatus;
use Hihaho\Sdk\Support\Payload;

final readonly class RubricScore
{
    /**
     * @param  int|string|null  $matchStatusValue  raw value; `$matchStatus` is null when the SDK does not know it yet
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public string $criterion,
        public ?float $score,
        public ?bool $passed,
        public ?float $passThreshold,
        public int|string|null $matchStatusValue,
        public ?RubricMatchStatus $matchStatus,
        public ?string $captionExcerpt,
        private array $raw,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $p = new Payload($data);

        return new self(
            criterion: $p->string('criterion'),
            score: $p->nullableFloat('score'),
            passed: $p->nullableBool('passed'),
            passThreshold: $p->nullableFloat('pass_threshold'),
            matchStatusValue: $p->scalar('match_status'),
            matchStatus: $p->enum('match_status', RubricMatchStatus::class),
            captionExcerpt: $p->nullableString('caption_excerpt'),
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
