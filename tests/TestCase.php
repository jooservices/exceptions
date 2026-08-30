<?php

declare(strict_types=1);

namespace JOOservices\Exceptions\Tests;

use JOOservices\Exceptions\Base\AbstractContextAwareException;
use PHPUnit\Framework\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function tearDown(): void
    {
        AbstractContextAwareException::removeRedactor();

        parent::tearDown();
    }
}
