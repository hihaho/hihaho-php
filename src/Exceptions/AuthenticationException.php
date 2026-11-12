<?php declare(strict_types=1);

namespace Hihaho\Sdk\Exceptions;

/**
 * 401, or a redirect the API sends to a flagged account.
 */
final class AuthenticationException extends HttpException {}
