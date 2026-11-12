<?php declare(strict_types=1);

namespace Hihaho\Sdk\Input\Questions;

use Hihaho\Sdk\Input\Fields;

final readonly class Answer
{
    /**
     * @param  AnswerAction|null  $action  null continues playing; the API needs an action on every answer
     * @param  int|null  $points  only stored when the question counts points per answer
     */
    public function __construct(
        public string $text,
        public bool $isCorrect,
        public ?AnswerAction $action = null,
        public ?string $explanation = null,
        public ?int $points = null,
    ) {}

    public static function correct(string $text, ?AnswerAction $action = null, ?string $explanation = null, ?int $points = null): self
    {
        return new self($text, true, $action, $explanation, $points);
    }

    public static function wrong(string $text, ?AnswerAction $action = null, ?string $explanation = null, ?int $points = null): self
    {
        return new self($text, false, $action, $explanation, $points);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return Fields::build([
            'text' => $this->text,
            'is_correct' => $this->isCorrect,
            'action' => ($this->action ?? AnswerAction::continuePlaying())->toArray(),
            'explanation' => $this->explanation,
            'points' => $this->points,
        ]);
    }
}
