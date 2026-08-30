<?php

declare(strict_types=1);

namespace JOOservices\Exceptions\Tests\Fixtures;

use JOOservices\Exceptions\Base\AbstractContextAwareLogicException;
use JOOservices\Exceptions\Support\ExceptionContext;

final class LogicExceptionFixture extends AbstractContextAwareLogicException
{
    public function errorCode(): string
    {
        return 'exceptions.test.logic';
    }

    protected function copyWithContext(ExceptionContext $context): static
    {
        return new self($this->getMessage(), $this->getCode(), $this->getPrevious(), $context);
    }
}
