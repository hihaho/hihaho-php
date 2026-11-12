<?php declare(strict_types=1);

namespace Hihaho\Sdk\Enums;

/**
 * How an adaptive condition compares the viewer's score on a subject with its percentage.
 * `Within` matches a score at most 5 percentage points away from it.
 */
enum AdaptiveComparison: string
{
    case AtLeast = '>=';
    case LessThan = '<';
    case Within = '~';
}
