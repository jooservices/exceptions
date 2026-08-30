<?php

declare(strict_types=1);

namespace JOOservices\Exceptions\Tests\Unit;

use JOOservices\Exceptions\Contracts\JOOExceptionInterface;
use JOOservices\Exceptions\Contracts\JOORuntimeExceptionInterface;
use JOOservices\Exceptions\Tests\Fixtures\PlainRuntimeExceptionFixture;
use JOOservices\Exceptions\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Throwable;

final class AbstractJOORuntimeExceptionTest extends TestCase
{
    #[Test]
    public function preservesSplSemantics(): void
    {
        $exception = self::make();

        self::assertInstanceOf(RuntimeException::class, $exception);
        self::assertInstanceOf(JOOExceptionInterface::class, $exception);
        self::assertInstanceOf(JOORuntimeExceptionInterface::class, $exception);
    }

    #[Test]
    public function passesConstructorArgumentsThrough(): void
    {
        $previous = new RuntimeException('root');
        $exception = self::make('boom', 42, $previous);

        self::assertSame('boom', $exception->getMessage());
        self::assertSame(42, $exception->getCode());
        self::assertSame($previous, $exception->getPrevious());
    }

    #[Test]
    public function defaultsMessageCodeAndPrevious(): void
    {
        $exception = self::make();

        self::assertSame('', $exception->getMessage());
        self::assertSame(0, $exception->getCode());
        self::assertNull($exception->getPrevious());
    }

    private static function make(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
    ): Throwable {
        return new PlainRuntimeExceptionFixture($message, $code, $previous);
    }
}
