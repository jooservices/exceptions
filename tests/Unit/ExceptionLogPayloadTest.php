<?php

declare(strict_types=1);

namespace JOOservices\Exceptions\Tests\Unit;

use JOOservices\Exceptions\Support\ExceptionLogPayload;
use JOOservices\Exceptions\Tests\Fixtures\PlainRuntimeExceptionFixture;
use JOOservices\Exceptions\Tests\TestCase;
use LogicException;
use PHPUnit\Framework\Attributes\Test;

final class ExceptionLogPayloadTest extends TestCase
{
    /**
     * @throws LogicException
     */
    #[Test]
    public function buildsTheStablePayloadShape(): void
    {
        $exception = new PlainRuntimeExceptionFixture('boom');

        $payload = (new ExceptionLogPayload())->build(
            $exception,
            'exceptions.test.payload',
            'error',
            ['context' => 'redacted'],
        );

        self::assertSame([
            'message' => 'boom',
            'class' => PlainRuntimeExceptionFixture::class,
            'error_code' => 'exceptions.test.payload',
            'log_level' => 'error',
            'log_schema' => ExceptionLogPayload::SCHEMA_VERSION,
            'context' => ['context' => 'redacted'],
            'previous' => [],
        ], $payload);
    }

    /**
     * @throws LogicException
     */
    #[Test]
    public function schemaVersionIsBakedIntoThePayload(): void
    {
        $payload = (new ExceptionLogPayload())->build(
            new PlainRuntimeExceptionFixture(),
            'exception.generic',
            'error',
            [],
        );

        self::assertSame(ExceptionLogPayload::SCHEMA_VERSION, $payload['log_schema']);
    }

    /**
     * @throws LogicException
     */
    #[Test]
    public function summarisesPreviousChainAsClassAndCodeOnly(): void
    {
        $deepest = new PlainRuntimeExceptionFixture('deepest message', 1);
        $middle = new PlainRuntimeExceptionFixture('middle message', 2, $deepest);
        $outer = new PlainRuntimeExceptionFixture('outer message', 3, $middle);

        $payload = (new ExceptionLogPayload())->build($outer, 'exception.generic', 'error', []);

        self::assertSame([
            ['class' => PlainRuntimeExceptionFixture::class, 'code' => 2],
            ['class' => PlainRuntimeExceptionFixture::class, 'code' => 1],
        ], $payload['previous']);

        self::assertStringNotContainsString('middle message', (string) json_encode($payload));
        self::assertStringNotContainsString('deepest message', (string) json_encode($payload));
    }

    /**
     * @throws LogicException
     */
    #[Test]
    public function rejectsInvalidErrorCodes(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Invalid error code');

        (new ExceptionLogPayload())->build(
            new PlainRuntimeExceptionFixture(),
            'NoDotsHere',
            'error',
            [],
        );
    }

    /**
     * @throws LogicException
     */
    #[Test]
    public function rejectsInvalidLogLevels(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Invalid log level');

        (new ExceptionLogPayload())->build(
            new PlainRuntimeExceptionFixture(),
            'exception.generic',
            'warn',
            [],
        );
    }

    /**
     * @throws LogicException
     */
    #[Test]
    public function acceptsTheGenericErrorCode(): void
    {
        $payload = (new ExceptionLogPayload())->build(
            new PlainRuntimeExceptionFixture(),
            'exception.generic',
            'debug',
            [],
        );

        self::assertSame('exception.generic', $payload['error_code']);
        self::assertSame('debug', $payload['log_level']);
    }
}
