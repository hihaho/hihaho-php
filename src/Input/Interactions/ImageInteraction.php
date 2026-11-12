<?php declare(strict_types=1);

namespace Hihaho\Sdk\Input\Interactions;

use Hihaho\Sdk\Enums\InteractionType;
use Hihaho\Sdk\Input\Fields;

final readonly class ImageInteraction extends InteractionInput
{
    public function __construct(
        public float $startTime,
        public float $endTime,
        public string $imageUrl,
        public ?string $title = null,
        public ?bool $shouldFadeVolume = null,
        public ?Box $box = null,
        public ?string $backgroundColor = null,
        public ?bool $fadeIn = null,
        public ?bool $fadeOut = null,
        public ?Popup $popup = null,
        public ?Tooltip $tooltip = null,
        public ?Action $action = null,
        public ?string $objectFit = null,
        public ?float $opacity = null,
        public ?bool $clickThrough = null,
        public ?bool $playGifOnMouseover = null,
        ?string $ref = null,
        ?AdaptiveCondition $adaptiveCondition = null,
        ?AdaptiveCompareCondition $adaptiveCompareCondition = null,
    ) {
        parent::__construct($ref, $adaptiveCondition, $adaptiveCompareCondition);
    }

    public function type(): InteractionType
    {
        return InteractionType::Image;
    }

    public function toArray(): array
    {
        return Fields::build([
            'type' => $this->type()->value,
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
            'image_url' => $this->imageUrl,
            'title' => $this->title,
            'should_fade_volume' => $this->shouldFadeVolume,
            'style.background_color' => $this->backgroundColor,
            'should_fade_in' => $this->fadeIn,
            'should_fade_out' => $this->fadeOut,
            'style.object_fit' => $this->objectFit,
            'style.opacity' => $this->opacity,
            'attributes.should_click_through' => $this->clickThrough,
            'should_play_gif_on_mouseover' => $this->playGifOnMouseover,
        ], [
            $this->box?->toArray() ?? [],
            $this->popup?->toArray() ?? [],
            $this->tooltip?->toArray() ?? [],
            ['action' => $this->action?->toArray() ?? []],
            $this->sharedFields(),
        ]);
    }
}
