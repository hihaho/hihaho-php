<?php declare(strict_types=1);

namespace Hihaho\Sdk\Input\Questions;

use Hihaho\Sdk\Enums\AiPartialScoringStrategy;
use Hihaho\Sdk\Enums\AiReviewStrictness;
use Hihaho\Sdk\Enums\QuestionType;
use InvalidArgumentException;

/**
 * An open question, optionally judged by AI.
 */
final readonly class OpenQuestion extends QuestionInput
{
    /**
     * @param  list<Answer>  $answers  expected answers, at most 20
     * @param  list<array{id: int, folderId: int}>  $aiContextDocuments  documents in the video's own folder, at most 50; others are dropped
     */
    public function __construct(
        float $activeAt,
        ?string $text = null,
        public array $answers = [],
        public ?bool $judgedByAi = null,
        public ?bool $aiCoachingMode = null,
        public ?AiPartialScoringStrategy $aiPartialScoring = null,
        public ?bool $useTranscript = null,
        public ?bool $useGlobalKnowledge = null,
        public array $aiContextDocuments = [],
        QuestionSettings $settings = new QuestionSettings(),
        public ?AiReviewStrictness $reviewStrictness = null,
        ?string $ref = null,
    ) {
        if (count($answers) > 20) {
            throw new InvalidArgumentException('A question can have at most 20 answers.');
        }

        parent::__construct($activeAt, $text, $settings, $ref);
    }

    public function type(): QuestionType
    {
        return QuestionType::Open;
    }

    protected function typeFields(): array
    {
        return [
            'answers' => array_map(fn (Answer $answer): array => $answer->toArray(), $this->answers),
            'should_be_judged_via_ai' => $this->judgedByAi,
            'ai_coaching_mode' => $this->aiCoachingMode,
            'ai_partial_scoring_strategy' => $this->aiPartialScoring?->value,
            'review_strictness' => $this->reviewStrictness?->value,
            'use_transcript' => $this->useTranscript,
            'use_global_knowledge' => $this->useGlobalKnowledge,
            'overrides_ai_context_documents' => $this->aiContextDocuments === [] ? null : true,
            'ai_context_documents' => array_map(fn (array $document): array => ['id' => $document['id'], 'video_container_id' => $document['folderId']], $this->aiContextDocuments),
        ];
    }
}
