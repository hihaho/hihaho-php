<?php declare(strict_types=1);

namespace Hihaho\Sdk\Input\Interactions;

use Hihaho\Sdk\Enums\InteractionType;

/**
 * One interaction for `POST v2/video/{video}/interaction/batch`. Each subclass takes only the
 * fields the API stores for its type. Times are in seconds.
 *
 * `ref` names the interaction within one request, so a jump action in the same request can target
 * it with `Action::jumpToInteractionRef()`. The API answers 422 when two items use the same ref.
 */
abstract readonly class InteractionInput
{
    /**
     * @param  string|null  $ref  at most 255 characters
     */
    public function __construct(
        public ?string $ref = null,
        public ?AdaptiveCondition $adaptiveCondition = null,
        public ?AdaptiveCompareCondition $adaptiveCompareCondition = null,
    ) {}

    abstract public function type(): InteractionType;

    /**
     * @return array<string, mixed>
     */
    abstract public function toArray(): array;

    /**
     * Fields every interaction type takes.
     *
     * @return array<string, mixed>
     */
    protected function sharedFields(): array
    {
        return [
            'ref' => $this->ref,
            'adaptive_condition' => $this->adaptiveCondition?->toArray(),
            'adaptive_compare_condition' => $this->adaptiveCompareCondition?->toArray(),
        ];
    }
}
