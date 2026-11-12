<?php declare(strict_types=1);

namespace Hihaho\Sdk\Enums;

/**
 * The statuses `PUT v2/video/{video}` accepts. `VideoStatus` also holds the read-only ones.
 */
enum SettableVideoStatus: int
{
    case Offline = 0;
    case EmbedOnly = 1;
    case Unlisted = 2;
    case Published = 3;
    case AllowedViewersOnly = 4;
}
