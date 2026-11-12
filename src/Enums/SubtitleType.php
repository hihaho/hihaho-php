<?php declare(strict_types=1);

namespace Hihaho\Sdk\Enums;

/**
 * Subtitle file format.
 */
enum SubtitleType: string
{
    case Srt = 'srt';
    case Vtt = 'vtt';
}
