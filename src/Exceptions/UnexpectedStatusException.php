<?php declare(strict_types=1);

namespace Hihaho\Sdk\Exceptions;

/**
 * An unsuccessful status the SDK does not map to a narrower exception.
 */
final class UnexpectedStatusException extends HttpException {}
