<?php declare(strict_types=1);

namespace Hihaho\Sdk\Enums;

/**
 * Period to group view statistics by.
 */
enum GroupByPeriod: string
{
    case Day = 'day';
    case Week = 'week';
    case Month = 'month';
}
