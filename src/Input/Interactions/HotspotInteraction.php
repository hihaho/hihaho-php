<?php declare(strict_types=1);

namespace Hihaho\Sdk\Input\Interactions;

use Hihaho\Sdk\Enums\HotspotBlinkingSpeed;
use Hihaho\Sdk\Enums\InteractionType;
use Hihaho\Sdk\Enums\TooltipPlacement;
use Hihaho\Sdk\Input\Fields;

final readonly class HotspotInteraction extends InteractionInput
{
    public function __construct(
        public float $startTime,
        public float $endTime,
        public bool $showIndicator,
        public ?string $title = null,
        public ?bool $shouldFadeVolume = null,
        public ?Box $box = null,
        public ?string $backgroundColor = null,
        public ?string $text = null,
        public ?bool $verticalText = null,
        public ?Action $action = null,
        public ?string $indicatorColor = null,
        public ?string $textLabelColor = null,
        public ?HotspotBlinkingSpeed $blinkingSpeed = null,
        public ?float $textRenderScale = null,
        public ?TooltipPlacement $tooltipPlacement = null,
        ?string $ref = null,
        ?AdaptiveCondition $adaptiveCondition = null,
        ?AdaptiveCompareCondition $adaptiveCompareCondition = null,
    ) {
        parent::__construct($ref, $adaptiveCondition, $adaptiveCompareCondition);
    }

    public function type(): InteractionType
    {
        return InteractionType::Hotspot;
    }

    public function toArray(): array
    {
        return Fields::build([
            'type' => $this->type()->value,
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
            'attributes.should_show_indicator' => $this->showIndicator,
            'title' => $this->title,
            'should_fade_volume' => $this->shouldFadeVolume,
            'style.background_color' => $this->backgroundColor,
            'text' => $this->text,
            'attributes.should_use_vertical_text_orientation' => $this->verticalText,
            'attributes.indicator_color' => $this->indicatorColor,
            'attributes.text_label_color' => $this->textLabelColor,
            'attributes.blinking_speed' => $this->blinkingSpeed?->value,
            'attributes.text_render_scale' => $this->textRenderScale,
            'attributes.tooltip_placement' => $this->tooltipPlacement?->value,
        ], [
            $this->box?->toArray() ?? [],
            ['action' => $this->action?->toArray() ?? []],
            $this->sharedFields(),
        ]);
    }
}
