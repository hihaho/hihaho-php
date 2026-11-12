<?php declare(strict_types=1);

namespace Hihaho\Sdk\Enums;

/**
 * How a rubric criterion matched an answer.
 */
enum RubricMatchStatus: string
{
    case Matched = 'matched';
    case Unmatched = 'unmatched';
    case Ambiguous = 'ambiguous';
    case NotScored = 'not_scored';
}
