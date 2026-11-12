<?php declare(strict_types=1);

namespace Hihaho\Sdk\Enums;

/**
 * Publish status of a video, as read. Set it with `SettableVideoStatus`.
 */
enum VideoStatus: int
{
    case Offline = 0;
    case EmbedOnly = 1;
    case Unlisted = 2;
    case Published = 3;
    case AllowedViewersOnly = 4;
    case Processing = 5;
    case Failed = 6;
}
