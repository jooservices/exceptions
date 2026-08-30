<?php

declare(strict_types=1);

namespace JOOservices\Exceptions\Base;

use JOOservices\Exceptions\Contracts\JOOLogicExceptionInterface;
use LogicException;
use Throwable;

/**
 * Base logical exception class in the JOOservices ecosystem.
 *
 * Extend this class for programming bugs (invalid arguments, domain invariant
 * violations, invalid configuration mapping, calling unsupported operations).
 *
 * Strict rules:
 *  - No framework (Illuminate / Symfony) imports are allowed.
 *  - No HTTP semantics (status codes, response generators).
 *  - Every subclass must declare strict_types=1.
 */
abstract class AbstractJOOLogicException extends LogicException implements JOOLogicExceptionInterface
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
