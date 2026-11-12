<?php declare(strict_types=1);

namespace Hihaho\Sdk\Input\Interactions;

use Hihaho\Sdk\Enums\InteractionType;
use Hihaho\Sdk\Enums\ScrollDirection;
use Hihaho\Sdk\Enums\TextStroke;
use Hihaho\Sdk\Input\Fields;

/**
 * Scrolling text.
 */
final readonly class ScrollInteraction extends InteractionInput
{
    public function __construct(
        public float $startTime,
        public float $endTime,
        public ?string $title = null,
        public ?bool $shouldFadeVolume = null,
        public ?Box $box = null,
        public ?string $backgroundColor = null,
        public ?string $text = null,
        public ?bool $verticalText = null,
        public ?TextStroke $textStroke = null,
        public ?Padding $padding = null,
        public ?bool $fadeIn = null,
        public ?bool $fadeOut = null,
        public ?Tooltip $tooltip = null,
        public ?Action $action = null,
        public ?bool $flyIn = null,
        public ?int $scrollDuration = null,
        public ?ScrollDirection $scrollDirection = null,
        ?string $ref = null,
        ?AdaptiveCondition $adaptiveCondition = null,
        ?AdaptiveCompareCondition $adaptiveCompareCondition = null,
    ) {
        parent::__construct($ref, $adaptiveCondition, $adaptiveCompareCondition);
    }

    public function type(): InteractionType
    {
        return InteractionType::Scroll;
    }

    public function toArray(): array
    {
        return Fields::build([
            'type' => $this->type()->value,
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
            'title' => $this->title,
            'should_fade_volume' => $this->shouldFadeVolume,
            'style.background_color' => $this->backgroundColor,
            'text' => $this->text,
            'attributes.should_use_vertical_text_orientation' => $this->verticalText,
            'text_stroke' => $this->textStroke?->value,
            'should_fade_in' => $this->fadeIn,
            'should_fade_out' => $this->fadeOut,
            'attributes.should_fly_in' => $this->flyIn,
            'attributes.scroll_duration' => $this->scrollDuration,
            'attributes.scroll_direction' => $this->scrollDirection?->value,
        ], [
            $this->box?->toArray() ?? [],
            $this->padding?->toArray() ?? [],
            $this->tooltip?->toArray() ?? [],
            ['action' => $this->action?->toArray() ?? []],
            $this->sharedFields(),
        ]);
    }
}
