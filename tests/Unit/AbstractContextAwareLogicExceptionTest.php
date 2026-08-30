<?php

declare(strict_types=1);

namespace JOOservices\Exceptions\Tests\Unit;

use JOOservices\Exceptions\Contracts\ContextAwareExceptionInterface;
use JOOservices\Exceptions\Contracts\JOOLogicExceptionInterface;
use JOOservices\Exceptions\Contracts\LoggableExceptionInterface;
use JOOservices\Exceptions\Support\DefaultContextRedactor;
use JOOservices\Exceptions\Tests\Fixtures\LogicExceptionFixture;
use JOOservices\Exceptions\Tests\TestCase;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use Throwable;

final class AbstractContextAwareLogicExceptionTest extends TestCase
{
    #[Test]
    public function implementsTheExpectedContracts(): void
    {
        $exception = self::make();

        self::assertInstanceOf(LogicException::class, $exception);
        self::assertInstanceOf(JOOLogicExceptionInterface::class, $exception);
        self::assertInstanceOf(ContextAwareExceptionInterface::class, $exception);
        self::assertInstanceOf(LoggableExceptionInterface::class, $exception);
    }

    #[Test]
    public function carriesContextLikeTheRuntimeBase(): void
    {
        $exception = (new LogicExceptionFixture('invariant'))->withContext([
            'field' => 'config.path',
            'password' => 'secret',
        ]);

        self::assertSame(['field' => 'config.path', 'password' => 'secret'], $exception->getRawContext());
        self::assertSame([
            'field' => 'config.path',
            'password' => DefaultContextRedactor::REDACTED_VALUE,
        ], $exception->getContext());
    }

    #[Test]
    public function exposesDefaultMetadata(): void
    {
        $exception = new LogicExceptionFixture();

        self::assertSame('exceptions.test.logic', $exception->errorCode());
        self::assertSame('error', $exception->logLevel());
    }

    private static function make(): Throwable
    {
        return new LogicExceptionFixture();
    }
}
