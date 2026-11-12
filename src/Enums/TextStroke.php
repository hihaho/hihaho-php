<?php declare(strict_types=1);

namespace Hihaho\Sdk\Enums;

/**
 * Outline around interaction text.
 *
 * The API takes the lowercase case names.
 */
enum TextStroke: string
{
    case None = 'none';
    case Black = 'black';
    case White = 'white';
}
