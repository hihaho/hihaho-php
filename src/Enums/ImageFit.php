<?php declare(strict_types=1);

namespace Hihaho\Sdk\Enums;

/**
 * How a question image fits its box.
 */
enum ImageFit: string
{
    case Cover = 'cover';
    case Contain = 'contain';
}
