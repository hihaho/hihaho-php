<?php declare(strict_types=1);

namespace Hihaho\Sdk\Enums;

/**
 * Corner of the player where the subtitle button shows.
 */
enum CornerPosition: string
{
    case Default = 'default';
    case TopLeft = 'top-left';
    case TopRight = 'top-right';
    case BottomLeft = 'bottom-left';
    case BottomRight = 'bottom-right';
}
