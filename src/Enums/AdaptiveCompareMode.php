<?php declare(strict_types=1);

namespace Hihaho\Sdk\Enums;

/**
 * Which subject score an adaptive compare condition picks.
 */
enum AdaptiveCompareMode: string
{
    case Highest = 'highest';
    case Lowest = 'lowest';
    case HighestAbsolute = 'highest_absolute';
    case LowestAbsolute = 'lowest_absolute';
}
