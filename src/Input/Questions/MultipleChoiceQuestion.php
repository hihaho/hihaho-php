<?php declare(strict_types=1);

namespace Hihaho\Sdk\Input\Questions;

use Hihaho\Sdk\Enums\QuestionType;
use InvalidArgumentException;

/**
 * A multiple-choice question: one answer can be picked.
 */
final readonly class MultipleChoiceQuestion extends QuestionInput
{
    /**
     * @param  list<Answer>  $answers  at most 20
     */
    public function __construct(
        float $activeAt,
        ?string $text = null,
        public array $answers = [],
        QuestionSettings $settings = new QuestionSettings(),
        ?string $ref = null,
    ) {
        if (count($answers) > 20) {
            throw new InvalidArgumentException('A question can have at most 20 answers.');
        }

        parent::__construct($activeAt, $text, $settings, $ref);
    }

    public function type(): QuestionType
    {
        return QuestionType::MultipleChoice;
    }

    protected function typeFields(): array
    {
        return [
            'answers' => array_map(fn (Answer $answer): array => $answer->toArray(), $this->answers),
        ];
    }
}
