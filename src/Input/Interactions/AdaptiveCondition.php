<?php declare(strict_types=1);

namespace Hihaho\Sdk\Input\Interactions;

use Hihaho\Sdk\Enums\AdaptiveComparison;
use Hihaho\Sdk\Input\Fields;
use InvalidArgumentException;

/**
 * Compares the viewer's score on one adaptive subject with a percentage. On a jump-to interaction,
 * the player carries out `fallback` when the score does not pass.
 *
 * Adaptive subjects are made in the studio. The API answers 422 on the subject position when
 * adaptive testing is off for the video, or when the video has no subject at that position.
 */
final readonly class AdaptiveCondition
{
    /**
     * @param  int  $subjectPosition  the subject's position on the video, 1 to 3
     * @param  int  $percentage  0 to 100
     */
    public function __construct(
        public int $subjectPosition,
        public AdaptiveComparison $comparison,
        public int $percentage,
        public ?AdaptiveAction $fallback = null,
    ) {
        if ($subjectPosition < 1 || $subjectPosition > 3) {
            throw new InvalidArgumentException('An adaptive subject position must be between 1 and 3.');
        }

        if ($percentage < 0 || $percentage > 100) {
            throw new InvalidArgumentException('The percentage must be between 0 and 100.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return Fields::build([
            'subject_sort_order' => $this->subjectPosition,
            'comparison' => $this->comparison->value,
            'percentage' => $this->percentage,
            'fallback_action_type' => $this->fallback?->typeCode(),
            'fallback_action_value' => $this->fallback?->value,
        ]);
    }
}
