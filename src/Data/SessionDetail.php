<?php declare(strict_types=1);

namespace Hihaho\Sdk\Data;

use DateTimeImmutable;
use Hihaho\Sdk\Support\Payload;

/**
 * One viewing session with every answer.
 */
final readonly class SessionDetail
{
    /**
     * @param  array<array-key, mixed>  $variables
     * @param  list<SessionQuestion>  $questions
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public int $id,
        public int $videoId,
        public ?int $score,
        public int $maxScore,
        public float $scaledResult,
        public ?int $percentageToSucceed,
        public bool $passed,
        public bool $hasStarted,
        public bool $hasFinished,
        public ?DateTimeImmutable $sessionStartedAt,
        public ?DateTimeImmutable $videoClosedAt,
        public ?DateTimeImmutable $createdAt,
        public float $lastReachedPointSeconds,
        public float $lastReachedPointPercentage,
        public float $totalViewDurationSeconds,
        public array $variables,
        public array $questions,
        private array $raw,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $p = new Payload($data);

        return new self(
            id: $p->int('id'),
            videoId: $p->int('video_id'),
            score: $p->nullableInt('score'),
            maxScore: $p->int('max_score'),
            scaledResult: $p->float('scaled_result'),
            percentageToSucceed: $p->nullableInt('percentage_to_succeed'),
            passed: $p->bool('passed'),
            hasStarted: $p->bool('has_started'),
            hasFinished: $p->bool('has_finished'),
            sessionStartedAt: $p->date('session_started_at'),
            videoClosedAt: $p->date('video_closed_at'),
            createdAt: $p->date('created_at'),
            lastReachedPointSeconds: $p->float('last_reached_point_seconds'),
            lastReachedPointPercentage: $p->float('last_reached_point_percentage'),
            totalViewDurationSeconds: $p->float('total_view_duration_seconds'),
            variables: $p->array('variables'),
            questions: $p->list('questions', SessionQuestion::fromArray(...)),
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
