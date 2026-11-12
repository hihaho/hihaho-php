<?php declare(strict_types=1);

namespace Hihaho\Sdk\Data;

use DateTimeImmutable;
use Hihaho\Sdk\Support\Payload;

/**
 * How a viewer answered one question in a session. The answer id is in `raw()`: an int, or a list of ints.
 */
final readonly class SessionQuestion
{
    /**
     * @param  list<RubricScore>  $rubricScores
     * @param  list<SessionAnswerAttempt>  $retries
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public ?int $id,
        public string $text,
        public float $questionTime,
        public int $maxPoints,
        public int $scoredPoints,
        public string $answerText,
        public ?bool $isCorrectAnswer,
        public ?string $followUp,
        public ?DateTimeImmutable $shownAt,
        public ?DateTimeImmutable $answeredAt,
        public ?string $aiFeedbackText,
        public array $rubricScores,
        public array $retries,
        private array $raw,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $p = new Payload($data);

        return new self(
            id: $p->nullableInt('id'),
            text: $p->string('text'),
            questionTime: $p->float('question_time'),
            maxPoints: $p->int('max_points'),
            scoredPoints: $p->int('scored_points'),
            answerText: $p->string('answer_text'),
            isCorrectAnswer: $p->nullableBool('is_correct_answer'),
            followUp: $p->nullableString('follow_up'),
            shownAt: $p->date('shown_at'),
            answeredAt: $p->date('answered_at'),
            aiFeedbackText: $p->nullableString('ai_feedback_text'),
            rubricScores: $p->list('rubric_scores', RubricScore::fromArray(...)),
            retries: $p->list('retries', SessionAnswerAttempt::fromArray(...)),
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
