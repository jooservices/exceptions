<?php

declare(strict_types=1);

namespace JOOservices\Exceptions\Tests\Fixtures;

use JOOservices\Exceptions\Base\AbstractContextAwareException;
use JOOservices\Exceptions\Support\ExceptionContext;
use Throwable;

/**
 * Context exception with extra constructor state — must preserve it in
 * copyWithContext() without constructor-shape assumptions.
 */
final class StatefulContextExceptionFixture extends AbstractContextAwareException
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        ?ExceptionContext $context = null,
        public readonly string $state = 'state',
    ) {
        parent::__construct($message, $code, $previous, $context);
    }

    public function errorCode(): string
    {
        return 'exceptions.test.stateful';
    }

    protected function copyWithContext(ExceptionContext $context): static
    {
        return new self(
            $this->getMessage(),
            $this->getCode(),
            $this->getPrevious(),
            $context,
            $this->state,
        );
    }
}
