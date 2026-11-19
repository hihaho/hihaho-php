<?php declare(strict_types=1);

namespace Hihaho\Sdk\Input\Questions;

use Hihaho\Sdk\Enums\QuestionType;
use InvalidArgumentException;

/**
 * A question whose answers are images.
 */
final readonly class ImageQuestion extends QuestionInput
{
    /**
     * @param  non-empty-list<Answer>  $answers  at most 20
     */
    public function __construct(
        float $activeAt,
        public array $answers,
        ?string $text = null,
        public ?bool $canSelectMultipleAnswers = null,
        QuestionSettings $settings = new QuestionSettings(),
        public ?bool $enableAnswerImageZoom = null,
        ?string $ref = null,
    ) {
        if ($answers === []) {
            throw new InvalidArgumentException('This question type needs at least one answer.');
        }

        if (count($answers) > 20) {
            throw new InvalidArgumentException('A question can have at most 20 answers.');
        }

        parent::__construct($activeAt, $text, $settings, $ref);
    }

    public function type(): QuestionType
    {
        return QuestionType::Image;
    }

    protected function typeFields(): array
    {
        return [
            'can_select_multiple_answers' => $this->canSelectMultipleAnswers,
            'enable_answer_image_zoom' => $this->enableAnswerImageZoom,
            'answers' => array_map(fn (Answer $answer): array => $answer->toArray(), $this->answers),
        ];
    }
}
