<?php declare(strict_types=1);

namespace Hihaho\Sdk\Input\Interactions;

use Hihaho\Sdk\Input\Fields;
use InvalidArgumentException;

/**
 * The action for a tie between exactly these adaptive subjects in a compare condition.
 */
final readonly class AdaptiveTieOutcome
{
    /**
     * @param  list<int>  $subjectPositions  at most 3, each 1 to 3
     */
    public function __construct(
        public AdaptiveAction $action,
        public array $subjectPositions = [],
    ) {
        if (count($subjectPositions) > 3) {
            throw new InvalidArgumentException('A tie outcome can name at most 3 subjects.');
        }

        foreach ($subjectPositions as $position) {
            if ($position < 1 || $position > 3) {
                throw new InvalidArgumentException('An adaptive subject position must be between 1 and 3.');
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return Fields::build([
            'action_type' => $this->action->typeCode(),
            'action_value' => $this->action->value,
            'subject_sort_orders' => $this->subjectPositions,
        ]);
    }
}
