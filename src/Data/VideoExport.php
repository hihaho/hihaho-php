<?php declare(strict_types=1);

namespace Hihaho\Sdk\Data;

use Hihaho\Sdk\Support\Payload;

/**
 * The interactions, questions and subtitles of a video in import format. Capped at 300 ungrouped interactions, 125 questions and 15 subtitles.
 */
final readonly class VideoExport
{
    /**
     * @param  array<array-key, mixed>  $interactions
     * @param  array<array-key, mixed>  $questions
     * @param  array<array-key, mixed>  $subtitles
     * @param  array<array-key, mixed>  $interactionGroups
     * @param  array<array-key, mixed>  $questionGroups
     * @param  array<array-key, mixed>  $adaptiveLearningSubjects
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public array $interactions,
        public array $questions,
        public array $subtitles,
        public array $interactionGroups,
        public array $questionGroups,
        public array $adaptiveLearningSubjects,
        private array $raw,
    ) {}

    /**
     * @param  array<array-key, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $p = new Payload($data);

        return new self(
            interactions: $p->array('interactions'),
            questions: $p->array('questions'),
            subtitles: $p->array('subtitles'),
            interactionGroups: $p->array('interaction_groups'),
            questionGroups: $p->array('question_groups'),
            adaptiveLearningSubjects: $p->array('adaptive_learning_subjects'),
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
