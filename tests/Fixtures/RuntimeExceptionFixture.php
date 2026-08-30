<?php

declare(strict_types=1);

namespace JOOservices\Exceptions\Tests\Fixtures;

use JOOservices\Exceptions\Base\AbstractContextAwareException;
use JOOservices\Exceptions\Support\ExceptionContext;

final class RuntimeExceptionFixture extends AbstractContextAwareException
{
    public function errorCode(): string
    {
        return 'exceptions.test.fixture';
    }

    protected function copyWithContext(ExceptionContext $context): static
    {
        return new self($this->getMessage(), $this->getCode(), $this->getPrevious(), $context);
    }
}
