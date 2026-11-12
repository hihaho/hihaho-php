<?php declare(strict_types=1);

namespace Hihaho\Sdk\Input\Interactions;

use Hihaho\Sdk\Enums\InteractionType;
use Hihaho\Sdk\Enums\Locale;
use Hihaho\Sdk\Input\Fields;

final readonly class GlossaryInteraction extends InteractionInput
{
    /**
     * @param  list<GlossaryTerm>  $terms
     */
    public function __construct(
        public float $startTime,
        public array $terms,
        public ?float $endTime = null,
        public ?string $title = null,
        public ?bool $shouldFadeVolume = null,
        public ?Locale $locale = null,
        ?string $ref = null,
        ?AdaptiveCondition $adaptiveCondition = null,
        ?AdaptiveCompareCondition $adaptiveCompareCondition = null,
    ) {
        parent::__construct($ref, $adaptiveCondition, $adaptiveCompareCondition);
    }

    public function type(): InteractionType
    {
        return InteractionType::Glossary;
    }

    public function toArray(): array
    {
        return Fields::build([
            'type' => $this->type()->value,
            'start_time' => $this->startTime,
            'glossary_terms' => array_map(fn (GlossaryTerm $item): array => $item->toArray(), $this->terms),
            'end_time' => $this->endTime,
            'title' => $this->title,
            'should_fade_volume' => $this->shouldFadeVolume,
            'locale' => $this->locale?->value,
        ], [

            $this->sharedFields(),
        ]);
    }
}
