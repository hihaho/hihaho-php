<?php declare(strict_types=1);

namespace Hihaho\Sdk\Input\Questions;

use Hihaho\Sdk\Enums\QuestionType;

final readonly class RatingQuestion extends QuestionInput
{
    public function type(): QuestionType
    {
        return QuestionType::Rating;
    }
}
