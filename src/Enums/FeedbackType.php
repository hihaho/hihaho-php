<?php declare(strict_types=1);

namespace Hihaho\Sdk\Enums;

/**
 * How a question shows feedback.
 */
enum FeedbackType: string
{
    case NoFeedback = 'no_feedback';
    case Screen = 'screen';
    case Direct = 'direct';
}
