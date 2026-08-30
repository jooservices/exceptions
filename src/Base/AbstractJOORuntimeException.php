<?php

declare(strict_types=1);

namespace JOOservices\Exceptions\Base;

use JOOservices\Exceptions\Contracts\JOORuntimeExceptionInterface;
use RuntimeException;
use Throwable;

/**
 * Base operational exception class in the JOOservices ecosystem.
 *
 * Extend this class for runtime errors that represent operational conditions
 * (database failures, HTTP timeouts, API failures, external resource limits).
 *
 * Strict rules:
 *  - No framework (Illuminate / Symfony) imports are allowed.
 *  - No HTTP semantics (status codes, response generators).
 *  - Every subclass must declare strict_types=1.
 */
abstract class AbstractJOORuntimeException extends RuntimeException implements JOORuntimeExceptionInterface
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
