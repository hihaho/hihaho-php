<?php declare(strict_types=1);

namespace Hihaho\Sdk\Input\Interactions;

use Hihaho\Sdk\Enums\InteractionType;
use Hihaho\Sdk\Input\Fields;

/**
 * An invisible jump at a moment of the video. Without an action it jumps to `time` of `Action::jump()`.
 */
final readonly class JumpToInteraction extends InteractionInput
{
    public function __construct(
        public float $startTime,
        public ?float $endTime = null,
        public ?string $title = null,
        public ?bool $shouldFadeVolume = null,
        public ?Action $action = null,
        ?string $ref = null,
        ?AdaptiveCondition $adaptiveCondition = null,
        ?AdaptiveCompareCondition $adaptiveCompareCondition = null,
    ) {
        parent::__construct($ref, $adaptiveCondition, $adaptiveCompareCondition);
    }

    public function type(): InteractionType
    {
        return InteractionType::JumpTo;
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
            ['action' => $this->action?->toArray() ?? []],
            $this->sharedFields(),
        ]);
    }
}
