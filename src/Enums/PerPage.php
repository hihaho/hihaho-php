<?php declare(strict_types=1);

namespace Hihaho\Sdk\Enums;

/**
 * Page sizes the session and metadata lists accept. Any other value is a 422.
 */
enum PerPage: int
{
    case Ten = 10;
    case TwentyFive = 25;
    case Fifty = 50;
    case Hundred = 100;
}
