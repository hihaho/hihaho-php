<?php declare(strict_types=1);

namespace Hihaho\Sdk\Input\Questions;

use Hihaho\Sdk\Enums\QuestionType;

/**
 * An essay question: a long free-text answer, not judged.
 */
final readonly class EssayQuestion extends QuestionInput
{
    public function type(): QuestionType
    {
        return QuestionType::Essay;
    }
}
