<?php declare(strict_types=1);

namespace Hihaho\Sdk\Enums;

/**
 * Where a tooltip shows.
 */
enum TooltipPlacement: string
{
    case Auto = 'auto';
    case Top = 'top';
    case Middle = 'middle';
    case Bottom = 'bottom';
    case Left = 'left';
    case Right = 'right';
}
