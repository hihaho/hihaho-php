<?php declare(strict_types=1);

namespace Hihaho\Sdk\Input\Questions;

use Hihaho\Sdk\Enums\FeedbackTitleType;

/**
 * The title of the feedback screen: automatic, or custom text per outcome.
 */
final readonly class FeedbackTitle
{
    private function __construct(
        public FeedbackTitleType $type,
        public ?string $neutral = null,
        public ?string $correct = null,
        public ?string $incorrect = null,
        public ?string $partlyCorrect = null,
    ) {}

    public static function automatic(): self
    {
        return new self(FeedbackTitleType::Automatic);
    }

    public static function custom(?string $neutral = null, ?string $correct = null, ?string $incorrect = null, ?string $partlyCorrect = null): self
    {
        return new self(FeedbackTitleType::Custom, $neutral, $correct, $incorrect, $partlyCorrect);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'feedback_title_type' => $this->type->value,
            'feedback_title_custom_text' => [
                'neutral' => $this->neutral,
                'correct' => $this->correct,
                'incorrect' => $this->incorrect,
                'partly_correct' => $this->partlyCorrect,
            ],
        ];
    }
}
