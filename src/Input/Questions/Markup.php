<?php declare(strict_types=1);

namespace Hihaho\Sdk\Input\Questions;

final readonly class Markup
{
    public function __construct(
        public ?string $placeholder = null,
        public ?string $prefix = null,
        public ?string $suffix = null,
        public ?MarkupButton $backButton = null,
        public ?MarkupButton $feedbackActionButton = null,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'placeholder' => $this->placeholder,
            'prefix' => $this->prefix,
            'suffix' => $this->suffix,
            'back_button' => $this->backButton?->toArray(),
            'feedback_action_button' => $this->feedbackActionButton?->toArray(),
        ];
    }
}
