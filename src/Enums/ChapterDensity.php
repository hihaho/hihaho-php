<?php declare(strict_types=1);

namespace Hihaho\Sdk\Enums;

/**
 * How many chapters an AI chapter task makes.
 */
enum ChapterDensity: string
{
    case Fewer = 'fewer';
    case Default = 'default';
    case More = 'more';
}
