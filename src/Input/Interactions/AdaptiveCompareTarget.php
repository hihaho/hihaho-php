<?php declare(strict_types=1);

namespace Hihaho\Sdk\Input\Interactions;

use Hihaho\Sdk\Input\Fields;
use InvalidArgumentException;

/**
 * The action for one adaptive subject in a compare condition, carried out when that subject wins.
 */
final readonly class AdaptiveCompareTarget
{
    /**
     * @param  int  $subjectPosition  the subject's position on the video, 1 to 3
     */
    public function __construct(
        public int $subjectPosition,
        public AdaptiveAction $action,
    ) {
        if ($subjectPosition < 1 || $subjectPosition > 3) {
            throw new InvalidArgumentException('An adaptive subject position must be between 1 and 3.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return Fields::build([
            'subject_sort_order' => $this->subjectPosition,
            'action_type' => $this->action->typeCode(),
            'action_value' => $this->action->value,
        ]);
    }
}
