<?php declare(strict_types=1);

namespace Hihaho\Sdk\Enums;

/**
 * Corner of the video for a menu or frame button.
 *
 * The API takes the lowercase case names.
 */
enum MenuButtonPosition: string
{
    case BottomRight = 'bottom_right';
    case BottomLeft = 'bottom_left';
    case TopLeft = 'top_left';
    case TopRight = 'top_right';
}
