<?php declare(strict_types=1);

namespace Hihaho\Sdk\Input\Interactions;

use Hihaho\Sdk\Enums\ContinueButtonDisplayMode;
use Hihaho\Sdk\Enums\InteractionType;
use Hihaho\Sdk\Enums\PauseContent;
use Hihaho\Sdk\Enums\TextStroke;
use Hihaho\Sdk\Input\Fields;

final readonly class PauseInteraction extends InteractionInput
{
    public function __construct(
        public float $startTime,
        public ?float $endTime = null,
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
        public ?Action $action = null,
        public ?PauseContent $content = null,
        public ?string $imageUrl = null,
        public ?string $soundUrl = null,
        public ?bool $enableSound = null,
        public ?ContinueButtonDisplayMode $continueButton = null,
        public ?string $continueButtonLeft = null,
        public ?string $continueButtonTop = null,
        public ?string $objectFit = null,
        public ?float $opacity = null,
        public ?bool $clickThrough = null,
        public ?bool $blockPlayback = null,
        public ?bool $playGifOnMouseover = null,
        ?string $ref = null,
        ?AdaptiveCondition $adaptiveCondition = null,
        ?AdaptiveCompareCondition $adaptiveCompareCondition = null,
    ) {
        parent::__construct($ref, $adaptiveCondition, $adaptiveCompareCondition);
    }

    public function type(): InteractionType
    {
        return InteractionType::Pause;
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
            'content_mode' => $this->content?->value,
            'image_url' => $this->imageUrl,
            'sound_url' => $this->soundUrl,
            'should_enable_sound' => $this->enableSound,
            'continue_button_display_mode' => $this->continueButton?->value,
            'style.continue_button_left' => $this->continueButtonLeft,
            'style.continue_button_top' => $this->continueButtonTop,
            'style.object_fit' => $this->objectFit,
            'style.opacity' => $this->opacity,
            'attributes.should_click_through' => $this->clickThrough,
            'should_play_gif_on_mouseover' => $this->playGifOnMouseover,
        ], [
            $this->box?->toArray() ?? [],
            $this->padding?->toArray() ?? [],
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
            'should_block_playback' => $this->blockPlayback,
        ];
    }
}
