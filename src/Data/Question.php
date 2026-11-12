<?php declare(strict_types=1);

namespace Hihaho\Sdk\Data;

use DateTimeImmutable;
use Hihaho\Sdk\Enums\QuestionDifficulty;
use Hihaho\Sdk\Enums\QuestionType;
use Hihaho\Sdk\Support\Payload;

/**
 * A question as stored. `action` and `markup` are the internal shape, so they stay raw arrays. `answers` and `evaluationRubrics` are only filled when one question is fetched.
 */
final readonly class Question
{
    /**
     * @param  int|string|null  $typeValue  raw value; `$type` is null when the SDK does not know it yet
     * @param  int|string|null  $difficultyValue  raw value; `$difficulty` is null when the SDK does not know it yet
     * @param  array<array-key, mixed>|null  $action
     * @param  array<array-key, mixed>|null  $markup
     * @param  array<array-key, mixed>  $answers
     * @param  array<array-key, mixed>  $evaluationRubrics
     * @param  array<array-key, mixed>  $raw
     */
    public function __construct(
        public int $id,
        public int $videoId,
        public int|string|null $typeValue,
        public ?QuestionType $type,
        public ?string $text,
        public float $time,
        public ?int $points,
        public ?string $explanation,
        public bool $isAiJudged,
        public bool $isAiGenerated,
        public int|string|null $difficultyValue,
        public ?QuestionDifficulty $difficulty,
        public ?int $questionGroupId,
        public ?array $action,
        public ?array $markup,
        public array $answers,
        public array $evaluationRubrics,
        public ?DateTimeImmutable $createdAt,
        public ?DateTimeImmutable $updatedAt,
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
            typeValue: $p->scalar('type'),
            type: $p->enum('type', QuestionType::class),
            text: $p->nullableString('text'),
            time: $p->float('time'),
            points: $p->nullableInt('points'),
            explanation: $p->nullableString('explanation'),
            isAiJudged: $p->bool('is_ai_judged'),
            isAiGenerated: $p->bool('is_ai_generated'),
            difficultyValue: $p->scalar('difficulty'),
            difficulty: $p->enum('difficulty', QuestionDifficulty::class),
            questionGroupId: $p->nullableInt('question_group_id'),
            action: $p->nullableArray('action'),
            markup: $p->nullableArray('markup'),
            answers: $p->array('answers'),
            evaluationRubrics: $p->array('evaluation_rubrics'),
            createdAt: $p->date('created_at'),
            updatedAt: $p->date('updated_at'),
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
