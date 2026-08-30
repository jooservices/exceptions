<?php

declare(strict_types=1);

namespace JOOservices\Exceptions\Base;

use JOOservices\Exceptions\Concerns\ProvidesStructuredContext;
use JOOservices\Exceptions\Contracts\ContextAwareExceptionInterface;
use JOOservices\Exceptions\Contracts\LoggableExceptionInterface;
use JOOservices\Exceptions\Support\ExceptionContext;
use Throwable;

/**
 * Logic exception base with structured context and secret redaction.
 *
 * Use for programming / domain-invariant failures that still need diagnostic
 * context (invalid configuration paths, invariant violations). Shares the
 * same redactor registry as AbstractContextAwareException.
 *
 * @phpstan-consistent-constructor
 */
abstract class AbstractContextAwareLogicException extends AbstractJOOLogicException implements
    ContextAwareExceptionInterface,
    LoggableExceptionInterface
{
    use ProvidesStructuredContext;

    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        ?ExceptionContext $context = null,
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context ?? new ExceptionContext();
    }
}
