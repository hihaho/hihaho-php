<?php declare(strict_types=1);

namespace Hihaho\Sdk\Data;

use Hihaho\Sdk\Support\Payload;

/**
 * How often one answer was given.
 */
final readonly class AnswerStatistic
{
    /**
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public string $label,
        public int $answeredCount,
        public bool $isCorrectAnswer,
        private array $raw,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $p = new Payload($data);

        return new self(
            label: $p->string('label'),
            answeredCount: $p->int('answered_count'),
            isCorrectAnswer: $p->bool('is_correct_answer'),
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
