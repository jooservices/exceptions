<?php

declare(strict_types=1);

namespace JOOservices\Exceptions\Tests\Fixtures;

use JOOservices\Exceptions\Concerns\HasExceptionContext;
use JOOservices\Exceptions\Contracts\ContextAwareExceptionInterface;
use JOOservices\Exceptions\Contracts\LoggableExceptionInterface;
use JOOservices\Exceptions\Support\ExceptionContext;
use RuntimeException;
use Throwable;

/**
 * Trait-based exception extending a "third-party" SPL parent.
 */
final class TraitBasedExceptionFixture extends RuntimeException implements
    ContextAwareExceptionInterface,
    LoggableExceptionInterface
{
    use HasExceptionContext;

    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
        $this->initContext();
    }

    public function errorCode(): string
    {
        return 'exceptions.test.trait';
    }

    protected function copyWithContext(ExceptionContext $context): static
    {
        $copy = new self($this->getMessage(), $this->getCode(), $this->getPrevious());
        $copy->initContext($context->toArray());

        return $copy;
    }
}
