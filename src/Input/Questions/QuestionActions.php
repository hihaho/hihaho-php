<?php declare(strict_types=1);

namespace Hihaho\Sdk\Input\Questions;

/**
 * What happens after a correct answer, an incorrect answer, or an answer that matches none.
 */
final readonly class QuestionActions
{
    public function __construct(
        public ?AnswerAction $correct = null,
        public ?AnswerAction $incorrect = null,
        public ?AnswerAction $noMatch = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'correct' => $this->correct?->toArray(),
            'incorrect' => $this->incorrect?->toArray(),
            'no_match' => $this->noMatch?->toArray(),
        ];
    }
}
