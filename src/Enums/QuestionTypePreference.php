<?php declare(strict_types=1);

namespace Hihaho\Sdk\Enums;

/**
 * Which question types an AI question task makes.
 */
enum QuestionTypePreference: string
{
    case Mix = 'mix';
    case OpenOnly = 'open_only';
    case MultipleChoiceOnly = 'multiple_choice_only';
}
