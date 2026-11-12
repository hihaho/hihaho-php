<?php declare(strict_types=1);

namespace Hihaho\Sdk\Input\Interactions;

use Hihaho\Sdk\Enums\AdaptiveCompareMode;
use Hihaho\Sdk\Input\Fields;
use InvalidArgumentException;

/**
 * Compares the viewer's scores on up to 3 adaptive subjects and carries out the action of the
 * subject that wins. A tie uses the tie outcome that names exactly the tied subjects, or else
 * `default`. The player evaluates it on a jump-to interaction.
 *
 * Adaptive subjects are made in the studio. The API answers 422 on a subject position when
 * adaptive testing is off for the video, or when the video has no subject at that position.
 */
final readonly class AdaptiveCompareCondition
{
    /**
     * @param  list<AdaptiveCompareTarget>  $targets  1 to 3
     * @param  list<AdaptiveTieOutcome>  $tieOutcomes  at most 3
     */
    public function __construct(
        public AdaptiveCompareMode $mode,
        public AdaptiveAction $default,
        public array $targets,
        public array $tieOutcomes = [],
    ) {
        if ($targets === [] || count($targets) > 3) {
            throw new InvalidArgumentException('A compare condition needs 1 to 3 targets.');
        }

        if (count($tieOutcomes) > 3) {
            throw new InvalidArgumentException('A compare condition can have at most 3 tie outcomes.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return Fields::build([
            'comparison_mode' => $this->mode->value,
            'default_action_type' => $this->default->typeCode(),
            'default_action_value' => $this->default->value,
            'targets' => array_map(fn (AdaptiveCompareTarget $target): array => $target->toArray(), $this->targets),
            'tie_outcomes' => array_map(fn (AdaptiveTieOutcome $outcome): array => $outcome->toArray(), $this->tieOutcomes),
        ]);
    }
}
