<?php declare(strict_types=1);

namespace Hihaho\Sdk\Enums;

enum HotspotBlinkingSpeed: string
{
    case None = 'none';
    case VerySlow = 'very-slow';
    case Slow = 'slow';
    case Normal = 'normal';
    case Fast = 'fast';
    case VeryFast = 'very-fast';
}
