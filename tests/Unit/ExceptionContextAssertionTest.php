<?php

declare(strict_types=1);

namespace JOOservices\Exceptions\Tests\Unit;

use AssertionError;
use JOOservices\Exceptions\Contracts\LoggableExceptionInterface;
use JOOservices\Exceptions\Testing\ExceptionContextAssertion;
use JOOservices\Exceptions\Tests\Fixtures\RuntimeExceptionFixture;
use JOOservices\Exceptions\Tests\Fixtures\TraitBasedExceptionFixture;
use JOOservices\Exceptions\Tests\TestCase;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;

final class ExceptionContextAssertionTest extends TestCase
{
    /**
     * @throws AssertionError
     */
    #[Test]
    public function assertHasContextPassesOnMatch(): void
    {
        $exception = (new RuntimeExceptionFixture('boom'))->withContext(['a' => 1]);

        ExceptionContextAssertion::assertHasContext($exception, ['a' => 1]);

        $this->addToAssertionCount(1);
    }

    /**
     * @throws AssertionError
     */
    #[Test]
    public function assertHasContextFailsOnMismatch(): void
    {
        $exception = (new RuntimeExceptionFixture('boom'))->withContext(['a' => 1]);

        $this->expectException(AssertionError::class);
        $this->expectExceptionMessage('Exception context does not contain');

        ExceptionContextAssertion::assertHasContext($exception, ['b' => 2]);
    }

    /**
     * @throws AssertionError
     */
    #[Test]
    public function assertErrorCodePassesOnMatch(): void
    {
        ExceptionContextAssertion::assertErrorCode(
            new RuntimeExceptionFixture(),
            'exceptions.test.fixture',
        );

        $this->addToAssertionCount(1);
    }

    /**
     * @throws AssertionError
     */
    #[Test]
    public function assertErrorCodeFailsOnMismatch(): void
    {
        $this->expectException(AssertionError::class);
        $this->expectExceptionMessage('Expected error code');

        ExceptionContextAssertion::assertErrorCode(new RuntimeExceptionFixture(), 'other.code');
    }

    /**
     * @throws AssertionError
     */
    #[Test]
    public function assertLogLevelPassesOnMatch(): void
    {
        ExceptionContextAssertion::assertLogLevel(new RuntimeExceptionFixture(), 'error');

        $this->addToAssertionCount(1);
    }

    /**
     * @throws AssertionError
     */
    #[Test]
    public function assertLogLevelFailsOnMismatch(): void
    {
        $this->expectException(AssertionError::class);
        $this->expectExceptionMessage('Expected log level');

        ExceptionContextAssertion::assertLogLevel(new RuntimeExceptionFixture(), 'debug');
    }

    /**
     * @throws AssertionError
     */
    #[Test]
    public function assertContextKeyRedactedPassesWithDefaultPlaceholder(): void
    {
        $exception = (new RuntimeExceptionFixture('boom'))->withContext(['password' => 'x']);

        ExceptionContextAssertion::assertContextKeyRedacted($exception, 'password');

        $this->addToAssertionCount(1);
    }

    /**
     * @throws AssertionError
     */
    #[Test]
    public function assertContextKeyRedactedPassesWithCustomPlaceholder(): void
    {
        $exception = (new RuntimeExceptionFixture('boom'))->withContext(['password' => 'x']);

        $this->expectException(AssertionError::class);

        ExceptionContextAssertion::assertContextKeyRedacted($exception, 'password', '[MASKED]');
    }

    /**
     * @throws AssertionError
     */
    #[Test]
    public function assertContextKeyRedactedFailsWhenKeyIsNotRedacted(): void
    {
        $exception = (new RuntimeExceptionFixture('boom'))->withContext(['safe' => 'x']);

        $this->expectException(AssertionError::class);
        $this->expectExceptionMessage('to be redacted');

        ExceptionContextAssertion::assertContextKeyRedacted($exception, 'safe');
    }

    /**
     * @throws AssertionError
     */
    #[Test]
    public function assertLogPayloadSchemaPassesForWellFormedPayloads(): void
    {
        ExceptionContextAssertion::assertLogPayloadSchema(new RuntimeExceptionFixture('boom'));

        $this->addToAssertionCount(1);
    }

    /**
     * @throws AssertionError
     */
    #[Test]
    public function assertLogPayloadSchemaFailsForBrokenPayloads(): void
    {
        $broken = new class extends RuntimeException implements LoggableExceptionInterface {
            public function errorCode(): string
            {
                return 'exception.generic';
            }

            public function logLevel(): string
            {
                return 'error';
            }

            public function toLogArray(): array
            {
                return ['message' => 'x'];
            }
        };

        $this->expectException(AssertionError::class);
        $this->expectExceptionMessage('missing key');

        ExceptionContextAssertion::assertLogPayloadSchema($broken);
    }

    /**
     * @throws AssertionError
     */
    #[Test]
    public function assertLogPayloadSchemaFailsForWrongSchemaVersion(): void
    {
        $broken = new class extends RuntimeException implements LoggableExceptionInterface {
            public function errorCode(): string
            {
                return 'exception.generic';
            }

            public function logLevel(): string
            {
                return 'error';
            }

            public function toLogArray(): array
            {
                return [
                    'message' => 'x',
                    'class' => self::class,
                    'error_code' => 'exception.generic',
                    'log_level' => 'error',
                    'log_schema' => 'other.v9',
                    'context' => [],
                    'previous' => [],
                ];
            }
        };

        $this->expectException(AssertionError::class);
        $this->expectExceptionMessage('log schema');

        ExceptionContextAssertion::assertLogPayloadSchema($broken);
    }

    /**
     * @throws AssertionError
     */
    #[Test]
    public function assertLogPayloadSchemaFailsForWrongValueTypes(): void
    {
        $broken = new class extends RuntimeException implements LoggableExceptionInterface {
            public function errorCode(): string
            {
                return 'exception.generic';
            }

            public function logLevel(): string
            {
                return 'error';
            }

            public function toLogArray(): array
            {
                return [
                    'message' => 123,
                    'class' => self::class,
                    'error_code' => 'exception.generic',
                    'log_level' => 'error',
                    'log_schema' => \JOOservices\Exceptions\Support\ExceptionLogPayload::SCHEMA_VERSION,
                    'context' => [],
                    'previous' => [],
                ];
            }
        };

        $this->expectException(AssertionError::class);
        $this->expectExceptionMessage('unexpected value types');

        ExceptionContextAssertion::assertLogPayloadSchema($broken);
    }

    /**
     * @throws AssertionError
     * @throws LogicException
     */
    #[Test]
    public function assertNoValueInContextPassesWhenValueIsAbsent(): void
    {
        $exception = (new TraitBasedExceptionFixture('boom'))->withContext([
            'safe' => 'kept',
            'nested' => ['inner' => 'also-kept'],
            'after' => 1,
        ]);

        ExceptionContextAssertion::assertNoValueInContext($exception, 'secret-value');

        $this->addToAssertionCount(1);
    }

    /**
     * @throws AssertionError
     * @throws LogicException
     */
    #[Test]
    public function assertNoValueInContextDetectsNestedForbiddenValues(): void
    {
        $exception = (new TraitBasedExceptionFixture('boom'))->withContext([
            'nested' => ['inner' => 'secret-value'],
        ]);

        $this->expectException(AssertionError::class);
        $this->expectExceptionMessage('forbidden value');

        ExceptionContextAssertion::assertNoValueInContext($exception, 'secret-value');
    }

    /**
     * @throws AssertionError
     * @throws LogicException
     */
    #[Test]
    public function assertHasContextWorksWithTraitBasedExceptions(): void
    {
        $exception = (new TraitBasedExceptionFixture('boom'))->withContext(['a' => 1]);

        ExceptionContextAssertion::assertHasContext($exception, ['a' => 1]);

        $this->addToAssertionCount(1);
    }
}
