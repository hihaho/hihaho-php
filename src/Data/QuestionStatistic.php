<?php declare(strict_types=1);

namespace Hihaho\Sdk\Data;

use Hihaho\Sdk\Enums\QuestionType;
use Hihaho\Sdk\Support\Payload;

/**
 * Answer statistics of one question in a period. `averageRating` is only set for rating questions.
 */
final readonly class QuestionStatistic
{
    /**
     * @param  int|string|null  $questionTypeValue  raw value; `$questionType` is null when the SDK does not know it yet
     * @param  list<AnswerStatistic>  $answers
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public int $id,
        public string $questionText,
        public int|string|null $questionTypeValue,
        public ?QuestionType $questionType,
        public float $videoTime,
        public int $maxPoints,
        public int $totalGivenAnswers,
        public float $averageAnswerTimeSeconds,
        public ?float $averageRating,
        public ?int $totalCorrectAnswers,
        public ?float $percentageCorrectAnswers,
        public array $answers,
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
            questionText: $p->string('question_text'),
            questionTypeValue: $p->scalar('question_type'),
            questionType: $p->enum('question_type', QuestionType::class),
            videoTime: $p->float('video_time'),
            maxPoints: $p->int('max_points'),
            totalGivenAnswers: $p->int('total_given_answers'),
            averageAnswerTimeSeconds: $p->float('average_answer_time_seconds'),
            averageRating: $p->nullableFloat('average_rating'),
            totalCorrectAnswers: $p->nullableInt('total_correct_answers'),
            percentageCorrectAnswers: $p->nullableFloat('percentage_correct_answers'),
            answers: $p->list('answers', AnswerStatistic::fromArray(...)),
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
