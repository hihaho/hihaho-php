<?php declare(strict_types=1);

namespace Hihaho\Sdk\Input\Questions;

use Hihaho\Sdk\Enums\AnswerActionType;
use InvalidArgumentException;

/**
 * An extra button on a question or its feedback screen. An enabled button must jump, link or do
 * something else: the API saves a button that only continues playing or repeats the question as
 * switched off, so the SDK refuses one.
 */
final readonly class MarkupButton
{
    public function __construct(
        public string $name,
        public AnswerAction $action,
        public bool $isEnabled = true,
    ) {
        if ($isEnabled && in_array($action->type, [AnswerActionType::ContinuePlaying, AnswerActionType::RepeatQuestion], true)) {
            throw new InvalidArgumentException("The button \"{$name}\" only continues playing or repeats the question, which the API saves switched off. Give it another action, or pass isEnabled: false.");
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return ['is_enabled' => $this->isEnabled, 'name' => $this->name, 'action' => $this->action->toArray()];
    }
}
