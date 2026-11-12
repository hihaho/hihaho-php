<?php declare(strict_types=1);

namespace Hihaho\Sdk\Enums;

/**
 * Whether the feedback title is automatic or custom text.
 */
enum FeedbackTitleType: string
{
    case Automatic = 'automatic';
    case Custom = 'custom';
}
