<?php declare(strict_types=1);

namespace Hihaho\Sdk\Input;

use Hihaho\Sdk\Enums\ChapterDensity;
use Hihaho\Sdk\Enums\QuestionDifficulty;
use Hihaho\Sdk\Enums\QuestionTypePreference;

/**
 * Optional preferences for an AI task (`user_input`).
 */
final readonly class AiTaskOptions
{
    public function __construct(
        public ?ChapterDensity $chapterDensity = null,
        public ?QuestionDifficulty $questionDifficulty = null,
        public ?QuestionTypePreference $questionTypePreference = null,
    ) {}

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return array_filter([
            'chapter_density' => $this->chapterDensity?->value,
            'question_difficulty' => $this->questionDifficulty?->value,
            'question_type_preference' => $this->questionTypePreference?->value,
        ], fn (?string $value): bool => $value !== null);
    }
}
