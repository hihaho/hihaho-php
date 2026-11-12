<?php declare(strict_types=1);

namespace Hihaho\Sdk\Enums;

enum UploadVideoStatus: int
{
    case Registered = 0;
    case InProgress = 5;
    case Ready = 6;
    case LegacyComplete = 7;
    case UploadFailedOrAbandoned = 8;
    case PlatformProcessingFailed = 11;

    public function isComplete(): bool
    {
        return $this === self::Ready || $this === self::LegacyComplete;
    }

    public function isFailed(): bool
    {
        return $this === self::UploadFailedOrAbandoned || $this === self::PlatformProcessingFailed;
    }
}
