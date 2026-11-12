<?php declare(strict_types=1);

namespace Hihaho\Sdk\Input\Questions;

use Hihaho\Sdk\Enums\QuestionType;
use Hihaho\Sdk\Input\Fields;

/**
 * One question for `POST v2/video/{video}/question/batch`. `activeAt` is the moment in seconds.
 *
 * `ref` names the question within one request, so an answer or button action in the same request
 * can target it with `AnswerAction::jumpToQuestionRef()`. The API answers 422 when two questions
 * use the same ref.
 */
abstract readonly class QuestionInput
{
    /**
     * @param  string|null  $ref  at most 255 characters
     */
    public function __construct(
        public float $activeAt,
        public ?string $text = null,
        public QuestionSettings $settings = new QuestionSettings(),
        public ?string $ref = null,
    ) {}

    abstract public function type(): QuestionType;

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return Fields::build(
            ['type' => $this->type()->value, 'active_at' => $this->activeAt, 'text' => $this->text, 'ref' => $this->ref],
            [$this->settings->toArray(), $this->typeFields()],
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function typeFields(): array
    {
        return [];
    }
}
