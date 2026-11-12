<?php declare(strict_types=1);

namespace Hihaho\Sdk\Enums;

/**
 * How strictly AI judges an answer to an open question.
 */
enum AiReviewStrictness: int
{
    case VeryLenient = 1;
    case Lenient = 2;
    case Standard = 3;
    case Strict = 4;
    case VeryStrict = 5;
}
