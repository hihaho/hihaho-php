<?php declare(strict_types=1);

namespace Hihaho\Sdk\Input\Interactions;

use Hihaho\Sdk\Enums\InteractionActionType;
use InvalidArgumentException;

/**
 * What the player does when an adaptive condition picks this outcome. The factories cover the
 * actions the player carries out for an adaptive outcome. Jump targets are ids that already exist.
 */
final readonly class AdaptiveAction
{
    private function __construct(
        public InteractionActionType $type,
        public ?string $value = null,
    ) {}

    public static function none(): self
    {
        return new self(InteractionActionType::NoAction);
    }

    public static function jump(float $toSeconds): self
    {
        if ($toSeconds < 0) {
            throw new InvalidArgumentException('A jump time cannot be negative.');
        }

        $centiseconds = (int) round($toSeconds * 100);

        $time = sprintf(
            '%02d:%02d:%02d.%02d',
            intdiv($centiseconds, 360_000),
            intdiv($centiseconds, 6_000) % 60,
            intdiv($centiseconds, 100) % 60,
            $centiseconds % 100,
        );

        return new self(InteractionActionType::Jump, $time);
    }

    public static function jumpToStart(): self
    {
        return new self(InteractionActionType::JumpToStart);
    }

    public static function jumpToEnd(): self
    {
        return new self(InteractionActionType::JumpToEnd);
    }

    public static function jumpToInteraction(int $interactionId): self
    {
        return new self(InteractionActionType::JumpToInteraction, "interaction-id-{$interactionId}");
    }

    public static function jumpToQuestion(int $questionId): self
    {
        return new self(InteractionActionType::JumpToInteraction, "question-id-{$questionId}");
    }

    public static function showScoreScreen(): self
    {
        return new self(InteractionActionType::ShowScoreScreen);
    }

    /**
     * The numeric action type code the adaptive fields take.
     */
    public function typeCode(): int
    {
        return match ($this->type) {
            InteractionActionType::NoAction => 0,
            InteractionActionType::Jump => 2,
            InteractionActionType::JumpToStart => 3,
            InteractionActionType::JumpToEnd => 4,
            InteractionActionType::JumpToInteraction => 5,
            InteractionActionType::ShowScoreScreen => 6,
            default => throw new InvalidArgumentException("Action type {$this->type->value} is not an adaptive action."),
        };
    }
}
