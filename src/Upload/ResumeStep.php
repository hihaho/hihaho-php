<?php declare(strict_types=1);

namespace Hihaho\Sdk\Upload;

/**
 * The step where `uploads()->resume()` continues a failed upload.
 *
 * @internal
 */
enum ResumeStep
{
    case Send;
    case Finish;
    case Status;
}
