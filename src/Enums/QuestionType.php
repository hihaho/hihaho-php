<?php declare(strict_types=1);

namespace Hihaho\Sdk\Enums;

enum QuestionType: string
{
    case Essay = 'essay';
    case MultipleChoice = 'mc';
    case Image = 'image';
    case MultipleResponse = 'mr';
    case Open = 'open';
    case Rating = 'rating';
    case Vacancy = 'vacancy';
}
