<?php declare(strict_types=1);

namespace Hihaho\Sdk\Data;

use DateTimeImmutable;
use Hihaho\Sdk\Enums\QuestionType;
use Hihaho\Sdk\Support\Payload;

/**
 * Answer statistics of one question.
 */
final readonly class QuestionStatisticDetail
{
    /**
     * @param  int|string|null  $typeValue  raw value; `$type` is null when the SDK does not know it yet
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public int $id,
        public ?string $title,
        public int|string|null $typeValue,
        public ?QuestionType $type,
        public float $activeAt,
        public ?int $amountAnswers,
        public ?int $amountCorrectAnswers,
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
            title: $p->nullableString('title'),
            typeValue: $p->scalar('type'),
            type: $p->enum('type', QuestionType::class),
            activeAt: $p->float('active_at'),
            amountAnswers: $p->nullableInt('amount_answers'),
            amountCorrectAnswers: $p->nullableInt('amount_correct_answers'),
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
