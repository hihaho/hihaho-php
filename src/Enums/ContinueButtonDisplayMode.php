<?php declare(strict_types=1);

namespace Hihaho\Sdk\Enums;

/**
 * How the continue button of a pause shows.
 */
enum ContinueButtonDisplayMode: string
{
    case NoButton = 'no_button';
    case IconOnly = 'icon_only';
    case IconAndText = 'icon_and_text';
    case TextOnly = 'text_only';
}
