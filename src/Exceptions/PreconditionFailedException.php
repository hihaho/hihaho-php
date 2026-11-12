<?php declare(strict_types=1);

namespace Hihaho\Sdk\Exceptions;

/**
 * 412: a folder setting blocks the request, for example aggregated statistics.
 */
final class PreconditionFailedException extends HttpException {}
