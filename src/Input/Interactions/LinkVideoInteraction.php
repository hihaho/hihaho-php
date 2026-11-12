<?php declare(strict_types=1);

namespace Hihaho\Sdk\Input\Interactions;

use Hihaho\Sdk\Enums\InteractionType;
use Hihaho\Sdk\Enums\PreviousVideoButtonMode;
use Hihaho\Sdk\Enums\TextStroke;
use Hihaho\Sdk\Input\Fields;

/**
 * A button that opens another hihaho video.
 */
final readonly class LinkVideoInteraction extends InteractionInput
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
        public ?float $targetStartTime = null,
        public ?PreviousVideoButtonMode $previousVideoButton = null,
        public ?string $previousVideoButtonText = null,
        ?string $ref = null,
        ?AdaptiveCondition $adaptiveCondition = null,
        ?AdaptiveCompareCondition $adaptiveCompareCondition = null,
    ) {
        parent::__construct($ref, $adaptiveCondition, $adaptiveCompareCondition);
    }

    public function type(): InteractionType
    {
        return InteractionType::LinkVideo;
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
        ], [
            $this->box?->toArray() ?? [],
            $this->padding?->toArray() ?? [],
            $this->tooltip?->toArray() ?? [],
            ['action' => $this->action instanceof Action ? $this->action->toArray($this->actionExtras()) : Fields::build($this->actionExtras())],
            $this->sharedFields(),
        ]);
    }

    /**
     * Action fields that belong to this interaction type, sent even without an action.
     *
     * @return array<string, mixed>
     */
    private function actionExtras(): array
    {
        return [
            'link_video_target_custom_start_time' => $this->targetStartTime,
            'previous_video_button_mode' => $this->previousVideoButton?->value,
            'previous_video_button_text' => $this->previousVideoButtonText,
        ];
    }
}
