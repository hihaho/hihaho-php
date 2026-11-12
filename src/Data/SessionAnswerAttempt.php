<?php declare(strict_types=1);

namespace Hihaho\Sdk\Data;

use DateTimeImmutable;
use Hihaho\Sdk\Support\Payload;

/**
 * An earlier attempt at a question. The answer id is in `raw()`: an int, or a list of ints.
 */
final readonly class SessionAnswerAttempt
{
    /**
     * @param  list<RubricScore>  $rubricScores
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public string $answerText,
        public ?bool $isCorrectAnswer,
        public ?string $followUp,
        public ?DateTimeImmutable $shownAt,
        public ?DateTimeImmutable $answeredAt,
        public ?string $aiFollowUpQuestion,
        public ?string $aiFeedbackText,
        public array $rubricScores,
        private array $raw,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $p = new Payload($data);

        return new self(
            answerText: $p->string('answer_text'),
            isCorrectAnswer: $p->nullableBool('is_correct_answer'),
            followUp: $p->nullableString('follow_up'),
            shownAt: $p->date('shown_at'),
            answeredAt: $p->date('answered_at'),
            aiFollowUpQuestion: $p->nullableString('ai_follow_up_question'),
            aiFeedbackText: $p->nullableString('ai_feedback_text'),
            rubricScores: $p->list('rubric_scores', RubricScore::fromArray(...)),
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
