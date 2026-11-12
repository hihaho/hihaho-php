<?php declare(strict_types=1);

namespace Hihaho\Sdk\Exceptions;

use RuntimeException;

/**
 * Base class of the SDK exceptions, for every API, transport and upload failure.
 */
abstract class HihahoException extends RuntimeException {}
