<?php declare(strict_types=1);

namespace Hihaho\Sdk\Enums;

/**
 * How the image of an image, frame or pause interaction fits.
 */
enum ObjectFit: string
{
    /** The image stays inside the interaction's box. */
    case Default = '';
    /** The image fills the whole video. */
    case Cover = 'cover';
}
