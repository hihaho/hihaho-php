<?php declare(strict_types=1);

namespace Hihaho\Sdk\Input\Interactions;

use Hihaho\Sdk\Enums\InteractionType;
use Hihaho\Sdk\Enums\MenuPosition;
use Hihaho\Sdk\Input\Fields;

final readonly class MenuInteraction extends InteractionInput
{
    public function __construct(
        public float $startTime,
        public ?float $endTime = null,
        public ?string $title = null,
        public ?bool $shouldFadeVolume = null,
        public ?MenuPosition $position = null,
        public ?bool $pauseWhenShown = null,
        public ?bool $notTimeBound = null,
        public ?MenuOptions $menuOptions = null,
        public ?bool $showChapterNumbers = null,
        ?string $ref = null,
        ?AdaptiveCondition $adaptiveCondition = null,
        ?AdaptiveCompareCondition $adaptiveCompareCondition = null,
    ) {
        parent::__construct($ref, $adaptiveCondition, $adaptiveCompareCondition);
    }

    public function type(): InteractionType
    {
        return InteractionType::Menu;
    }

    public function toArray(): array
    {
        return Fields::build([
            'type' => $this->type()->value,
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
            'title' => $this->title,
            'should_fade_volume' => $this->shouldFadeVolume,
            'position' => $this->position?->value,
            'should_pause_when_shown' => $this->pauseWhenShown,
            'should_not_use_time' => $this->notTimeBound,
            'should_show_chapter_numbers' => $this->showChapterNumbers,
        ], [
            $this->menuOptions?->toArray() ?? [],
            $this->sharedFields(),
        ]);
    }
}
