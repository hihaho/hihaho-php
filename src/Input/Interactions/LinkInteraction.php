<?php declare(strict_types=1);

namespace Hihaho\Sdk\Input\Interactions;

use Hihaho\Sdk\Enums\InteractionType;
use Hihaho\Sdk\Input\Fields;

final readonly class LinkInteraction extends InteractionInput
{
    public function __construct(
        public float $startTime,
        public float $endTime,
        public ?string $title = null,
        public ?bool $shouldFadeVolume = null,
        public ?Box $box = null,
        public ?Tooltip $tooltip = null,
        public ?Action $action = null,
        ?string $ref = null,
        ?AdaptiveCondition $adaptiveCondition = null,
        ?AdaptiveCompareCondition $adaptiveCompareCondition = null,
    ) {
        parent::__construct($ref, $adaptiveCondition, $adaptiveCompareCondition);
    }

    public function type(): InteractionType
    {
        return InteractionType::Link;
    }

    public function toArray(): array
    {
        return Fields::build([
            'type' => $this->type()->value,
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
            'title' => $this->title,
            'should_fade_volume' => $this->shouldFadeVolume,
        ], [
            $this->box?->toArray() ?? [],
            $this->tooltip?->toArray() ?? [],
            ['action' => $this->action?->toArray() ?? []],
            $this->sharedFields(),
        ]);
    }
}
