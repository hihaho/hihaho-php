<?php declare(strict_types=1);

namespace Hihaho\Sdk\Input\Interactions;

use Hihaho\Sdk\Enums\FrameContinuePlaying;
use Hihaho\Sdk\Enums\InteractionType;
use Hihaho\Sdk\Enums\MenuButtonPosition;
use Hihaho\Sdk\Input\Fields;

/**
 * An embedded web page or video in a frame.
 */
final readonly class FrameInteraction extends InteractionInput
{
    public function __construct(
        public float $startTime,
        public ?float $endTime = null,
        public ?string $title = null,
        public ?bool $shouldFadeVolume = null,
        public ?Box $box = null,
        public ?string $backgroundColor = null,
        public ?bool $fadeIn = null,
        public ?bool $fadeOut = null,
        public ?Popup $popup = null,
        public ?Action $action = null,
        public ?bool $pauseVideo = null,
        public ?FrameContinuePlaying $continuePlaying = null,
        public ?MenuButtonPosition $buttonPosition = null,
        public ?bool $resumeVideoWithEvent = null,
        public ?bool $blockPlayback = null,
        public ?string $objectFit = null,
        public ?bool $stayAliveAfterHiding = null,
        ?string $ref = null,
        ?AdaptiveCondition $adaptiveCondition = null,
        ?AdaptiveCompareCondition $adaptiveCompareCondition = null,
    ) {
        parent::__construct($ref, $adaptiveCondition, $adaptiveCompareCondition);
    }

    public function type(): InteractionType
    {
        return InteractionType::Frame;
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
            'should_fade_in' => $this->fadeIn,
            'should_fade_out' => $this->fadeOut,
            'style.object_fit' => $this->objectFit,
            'attributes.should_stay_alive_after_hiding' => $this->stayAliveAfterHiding,
        ], [
            $this->box?->toArray() ?? [],
            $this->popup?->toArray() ?? [],
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
            'should_pause_video' => $this->pauseVideo,
            'frame_continue_playing_option' => $this->continuePlaying?->value,
            'frame_button_position' => $this->buttonPosition?->value,
            'should_resume_video_with_event' => $this->resumeVideoWithEvent,
            'should_block_playback' => $this->blockPlayback,
        ];
    }
}
