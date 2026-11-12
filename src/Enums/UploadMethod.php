<?php declare(strict_types=1);

namespace Hihaho\Sdk\Enums;

/**
 * How the file is sent to the upload URL.
 */
enum UploadMethod: string
{
    case Tus = 'tus';
    case Axios = 'axios';
    case Upchunk = 'upchunk';
}
