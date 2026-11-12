<?php declare(strict_types=1);

namespace Hihaho\Sdk\Data;

use DateTimeImmutable;
use Hihaho\Sdk\Support\Payload;

final readonly class AggregatedNumbers
{
    /**
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public ?DateTimeImmutable $aggregatedAt,
        public ?int $views,
        public ?int $startedViews,
        public ?int $finishedViews,
        public ?int $scoredViews,
        public ?int $passedViews,
        public int $totalAllowedViewers,
        public AggregatedInteractions $interactions,
        public AggregatedQuestions $questions,
        private array $raw,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $p = new Payload($data);

        return new self(
            aggregatedAt: $p->date('aggregated_at'),
            views: $p->nullableInt('views'),
            startedViews: $p->nullableInt('started_views'),
            finishedViews: $p->nullableInt('finished_views'),
            scoredViews: $p->nullableInt('scored_views'),
            passedViews: $p->nullableInt('passed_views'),
            totalAllowedViewers: $p->int('total_allowed_viewers'),
            interactions: AggregatedInteractions::fromArray($p->array('interactions')),
            questions: AggregatedQuestions::fromArray($p->array('questions')),
            raw: $data,
        );
    }

    /**
     * The response exactly as the API sent it, including fields this class does not type.
     *
     * @return array<array-key, mixed>
     */
    public function raw(): array
    {
        return $this->raw;
    }
}
