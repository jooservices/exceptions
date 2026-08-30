<?php

declare(strict_types=1);

namespace JOOservices\Exceptions\Tests\Unit;

use JOOservices\Exceptions\Contracts\JOOExceptionInterface;
use JOOservices\Exceptions\Contracts\JOOLogicExceptionInterface;
use JOOservices\Exceptions\Tests\Fixtures\PlainLogicExceptionFixture;
use JOOservices\Exceptions\Tests\TestCase;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use Throwable;

final class AbstractJOOLogicExceptionTest extends TestCase
{
    #[Test]
    public function preservesSplSemantics(): void
    {
        $exception = self::make();

        self::assertInstanceOf(LogicException::class, $exception);
        self::assertInstanceOf(JOOExceptionInterface::class, $exception);
        self::assertInstanceOf(JOOLogicExceptionInterface::class, $exception);
    }

    #[Test]
    public function passesConstructorArgumentsThrough(): void
    {
        $exception = self::make('invariant violated', 7);

        self::assertSame('invariant violated', $exception->getMessage());
        self::assertSame(7, $exception->getCode());
    }

    private static function make(string $message = '', int $code = 0): Throwable
    {
        return new PlainLogicExceptionFixture($message, $code);
    }
}
