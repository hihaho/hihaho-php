<?php declare(strict_types=1);

namespace Hihaho\Sdk\Input\Interactions;

use Hihaho\Sdk\Enums\InteractionType;
use Hihaho\Sdk\Enums\MenuPosition;
use Hihaho\Sdk\Enums\VideoFontSize;
use Hihaho\Sdk\Input\Fields;

final readonly class ChapterInteraction extends InteractionInput
{
    /**
     * @param  list<Chapter>  $chapters
     */
    public function __construct(
        public float $startTime,
        public float $endTime,
        public array $chapters,
        public ?string $title = null,
        public ?bool $shouldFadeVolume = null,
        public ?MenuPosition $position = null,
        public ?bool $startCollapsed = null,
        public ?bool $collapseAfterChapterClick = null,
        public ?bool $pauseWhenShown = null,
        public ?bool $useMenuLayout = null,
        public ?VideoFontSize $desktopFontSize = null,
        public ?VideoFontSize $mobileFontSize = null,
        public ?bool $trackVisits = null,
        public ?MenuOptions $menuOptions = null,
        ?string $ref = null,
        ?AdaptiveCondition $adaptiveCondition = null,
        ?AdaptiveCompareCondition $adaptiveCompareCondition = null,
    ) {
        parent::__construct($ref, $adaptiveCondition, $adaptiveCompareCondition);
    }

    public function type(): InteractionType
    {
        return InteractionType::Chapter;
    }

    public function toArray(): array
    {
        return Fields::build([
            'type' => $this->type()->value,
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
            'chapters' => array_map(fn (Chapter $item): array => $item->toArray(), $this->chapters),
            'title' => $this->title,
            'should_fade_volume' => $this->shouldFadeVolume,
            'position' => $this->position?->value,
            'should_start_collapsed' => $this->startCollapsed,
            'should_collapse_after_menu_item_click' => $this->collapseAfterChapterClick,
            'should_pause_when_shown' => $this->pauseWhenShown,
            'should_use_menu_layout' => $this->useMenuLayout,
            'style.desktop_font_size' => $this->desktopFontSize?->value,
            'style.mobile_font_size' => $this->mobileFontSize?->value,
            'attributes.visit_tracking.enabled' => $this->trackVisits,
        ], [
            $this->menuOptions?->toArray() ?? [],
            $this->sharedFields(),
        ]);
    }
}
