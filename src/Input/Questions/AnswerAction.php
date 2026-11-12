<?php declare(strict_types=1);

namespace Hihaho\Sdk\Input\Questions;

use Hihaho\Sdk\Enums\AnswerActionType;
use Hihaho\Sdk\Input\Fields;

/**
 * What happens after an answer, or after a question outcome or button.
 *
 * A jump can target an existing item by id, or a question in the same request by its `ref`. The
 * API answers 422 on `target_ref` when no question in the request has that ref. A ref cannot target
 * an interaction: interactions are imported through another request.
 */
final readonly class AnswerAction
{
    /**
     * @param  array<string, mixed>  $fields
     */
    private function __construct(
        public AnswerActionType $type,
        private array $fields = [],
    ) {}

    public static function continuePlaying(): self
    {
        return new self(AnswerActionType::ContinuePlaying);
    }

    public static function repeatQuestion(): self
    {
        return new self(AnswerActionType::RepeatQuestion);
    }

    /**
     * Seconds, at most the video duration.
     */
    public static function jump(float $toSeconds): self
    {
        return new self(AnswerActionType::Jump, ['time' => $toSeconds]);
    }

    public static function jumpToStart(): self
    {
        return new self(AnswerActionType::JumpToStart);
    }

    public static function jumpToEnd(): self
    {
        return new self(AnswerActionType::JumpToEnd);
    }

    public static function jumpToInteraction(int $interactionId): self
    {
        return new self(AnswerActionType::JumpToInteraction, ['interaction_id' => $interactionId]);
    }

    public static function jumpToQuestion(int $questionId): self
    {
        return new self(AnswerActionType::JumpToInteraction, ['question_id' => $questionId]);
    }

    /**
     * Jumps to the question with this `ref` in the same request.
     */
    public static function jumpToQuestionRef(string $ref): self
    {
        return new self(AnswerActionType::JumpToInteraction, ['target_ref' => $ref]);
    }

    public static function jumpToChapter(int $chapterId): self
    {
        return new self(AnswerActionType::JumpToChapter, ['chapter_id' => $chapterId]);
    }

    public static function link(string $url, ?bool $openInSameWindow = null): self
    {
        return new self(AnswerActionType::Link, ['link' => $url, 'should_open_in_same_window' => $openInSameWindow]);
    }

    /**
     * Opens another published hihaho video, by its UUID.
     */
    public static function linkVideo(string $videoUuid): self
    {
        return new self(AnswerActionType::LinkVideo, ['link' => $videoUuid]);
    }

    public static function showScoreScreen(): self
    {
        return new self(AnswerActionType::ShowScoreScreen);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return Fields::build(['type' => $this->type->value, ...$this->fields]);
    }
}
