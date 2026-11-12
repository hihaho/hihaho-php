<?php declare(strict_types=1);

namespace Hihaho\Sdk\Data;

use Hihaho\Sdk\Support\Payload;

final readonly class AggregatedQuestions
{
    /**
     * @param  list<QuestionStatisticDetail>  $details
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public int $count,
        public int $totalGivenAnswers,
        public int $totalGivenCorrectAnswers,
        public array $details,
        private array $raw,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $p = new Payload($data);

        return new self(
            count: $p->int('count'),
            totalGivenAnswers: $p->int('total_given_answers'),
            totalGivenCorrectAnswers: $p->int('total_given_correct_answers'),
            details: $p->list('details', QuestionStatisticDetail::fromArray(...)),
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
