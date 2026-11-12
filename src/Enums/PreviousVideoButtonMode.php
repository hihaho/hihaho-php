<?php declare(strict_types=1);

namespace Hihaho\Sdk\Enums;

/**
 * Where the back-to-previous-video button shows after a video link.
 */
enum PreviousVideoButtonMode: string
{
    case None = 'none';
    case InVideoOnly = 'in_video_only';
    case InPlayerBarOnly = 'in_player_bar_only';
    case Everywhere = 'everywhere';
}
