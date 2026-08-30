<?php

declare(strict_types=1);

namespace JOOservices\Exceptions\Tests\Unit;

use JOOservices\Exceptions\Base\AbstractContextAwareException;
use JOOservices\Exceptions\Contracts\ContextAwareExceptionInterface;
use JOOservices\Exceptions\Contracts\JOORuntimeExceptionInterface;
use JOOservices\Exceptions\Contracts\LoggableExceptionInterface;
use JOOservices\Exceptions\Support\DefaultContextRedactor;
use JOOservices\Exceptions\Support\ExceptionContext;
use JOOservices\Exceptions\Support\ExceptionLogPayload;
use JOOservices\Exceptions\Tests\Fixtures\RuntimeExceptionFixture;
use JOOservices\Exceptions\Tests\Fixtures\SpyRedactor;
use JOOservices\Exceptions\Tests\Fixtures\StatefulContextExceptionFixture;
use JOOservices\Exceptions\Tests\TestCase;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use Throwable;

final class AbstractContextAwareExceptionTest extends TestCase
{
    #[Test]
    public function implementsTheExpectedContracts(): void
    {
        $exception = self::make();

        self::assertInstanceOf(JOORuntimeExceptionInterface::class, $exception);
        self::assertInstanceOf(ContextAwareExceptionInterface::class, $exception);
        self::assertInstanceOf(LoggableExceptionInterface::class, $exception);
    }

    #[Test]
    public function startsWithEmptyContext(): void
    {
        $exception = new RuntimeExceptionFixture('boom');

        self::assertSame([], $exception->getRawContext());
        self::assertSame([], $exception->getContext());
    }

    #[Test]
    public function acceptsPrebuiltContext(): void
    {
        $exception = new RuntimeExceptionFixture('boom', 0, null, new ExceptionContext(['a' => 1]));

        self::assertSame(['a' => 1], $exception->getRawContext());
    }

    #[Test]
    public function withContextReturnsNewImmutableInstance(): void
    {
        $original = new RuntimeExceptionFixture('boom');
        $augmented = $original->withContext(['a' => 1]);

        self::assertNotSame($original, $augmented);
        self::assertSame([], $original->getRawContext());
        self::assertSame(['a' => 1], $augmented->getRawContext());
    }

    #[Test]
    public function withContextMergesWithoutMutatingTheOriginal(): void
    {
        $original = (new RuntimeExceptionFixture('boom'))->withContext(['a' => 1]);
        $augmented = $original->withContext(['b' => 2]);

        self::assertSame(['a' => 1], $original->getRawContext());
        self::assertSame(['a' => 1, 'b' => 2], $augmented->getRawContext());
    }

    #[Test]
    public function getContextRedactsSensitiveKeys(): void
    {
        $exception = (new RuntimeExceptionFixture('boom'))->withContext([
            'password' => 'hunter2',
            'user_id' => 42,
        ]);

        self::assertSame([
            'password' => DefaultContextRedactor::REDACTED_VALUE,
            'user_id' => 42,
        ], $exception->getContext());

        self::assertSame(['password' => 'hunter2', 'user_id' => 42], $exception->getRawContext());
    }

    #[Test]
    public function exposesDefaultMetadata(): void
    {
        $exception = new RuntimeExceptionFixture();

        self::assertSame('exceptions.test.fixture', $exception->errorCode());
        self::assertSame('error', $exception->logLevel());
    }

    #[Test]
    public function fallsBackToGenericMetadataWithoutOverrides(): void
    {
        $exception = new class extends AbstractContextAwareException {
            protected function copyWithContext(ExceptionContext $context): static
            {
                return new self($this->getMessage(), $this->getCode(), $this->getPrevious(), $context);
            }
        };

        self::assertSame('exception.generic', $exception->errorCode());
        self::assertSame('error', $exception->logLevel());
    }

    /**
     * @throws LogicException
     */
    #[Test]
    public function toLogArrayReturnsTheStableShape(): void
    {
        $previous = new RuntimeExceptionFixture('root');
        $exception = new RuntimeExceptionFixture('boom', 5, $previous);

        $payload = $exception->toLogArray();

        self::assertSame('boom', $payload['message']);
        self::assertSame(RuntimeExceptionFixture::class, $payload['class']);
        self::assertSame('exceptions.test.fixture', $payload['error_code']);
        self::assertSame('error', $payload['log_level']);
        self::assertSame(ExceptionLogPayload::SCHEMA_VERSION, $payload['log_schema']);
        self::assertSame([], $payload['context']);
        self::assertSame([
            ['class' => RuntimeExceptionFixture::class, 'code' => 0],
        ], $payload['previous']);
    }

    #[Test]
    public function globalRedactorRegistryOverridesTheDefault(): void
    {
        $spy = new SpyRedactor();
        AbstractContextAwareException::setRedactor($spy);

        $exception = (new RuntimeExceptionFixture('boom'))->withContext(['password' => 'x']);

        self::assertSame(['password' => 'x'], $exception->getContext());
        self::assertSame(1, $spy->calls);

        AbstractContextAwareException::removeRedactor();

        $fresh = (new RuntimeExceptionFixture('boom'))->withContext(['password' => 'x']);

        self::assertSame(['password' => DefaultContextRedactor::REDACTED_VALUE], $fresh->getContext());
    }

    #[Test]
    public function defaultRedactorIsCachedPerProcess(): void
    {
        self::assertSame(
            AbstractContextAwareException::getRedactor(),
            AbstractContextAwareException::getRedactor(),
        );
        self::assertInstanceOf(DefaultContextRedactor::class, AbstractContextAwareException::getRedactor());
    }

    #[Test]
    public function instanceRedactorOverridesTheGlobalRegistry(): void
    {
        $spy = new SpyRedactor();

        $scoped = (new RuntimeExceptionFixture('boom'))
            ->withContext(['password' => 'x'])
            ->withRedactor($spy);

        $unscoped = (new RuntimeExceptionFixture('boom'))->withContext(['password' => 'x']);

        self::assertSame(['password' => 'x'], $scoped->getContext());
        self::assertSame(1, $spy->calls);
        self::assertSame(['password' => DefaultContextRedactor::REDACTED_VALUE], $unscoped->getContext());
        self::assertSame(1, $spy->calls);
    }

    #[Test]
    public function withRedactorPreservesContextAndInstanceIsolation(): void
    {
        $spy = new SpyRedactor();
        $original = (new RuntimeExceptionFixture('boom'))->withContext(['a' => 1]);
        $scoped = $original->withRedactor($spy);

        self::assertNotSame($original, $scoped);
        self::assertSame(['a' => 1], $scoped->getContext());
        self::assertSame(['a' => 1], $original->getContext());
        self::assertSame(1, $spy->calls);
        self::assertInstanceOf(DefaultContextRedactor::class, AbstractContextAwareException::getRedactor());
    }

    #[Test]
    public function copyWithContextPreservesStatefulConstructorInvariants(): void
    {
        $original = new StatefulContextExceptionFixture('boom', state: 'custom-state');
        $augmented = $original->withContext(['a' => 1]);

        self::assertSame('custom-state', $augmented->state);
        self::assertSame('boom', $augmented->getMessage());
        self::assertSame(['a' => 1], $augmented->getRawContext());
    }

    #[Test]
    public function copyWithContextPreservesMessageCodePreviousAndContext(): void
    {
        $previous = new RuntimeExceptionFixture('root');
        $original = new RuntimeExceptionFixture('boom', 7, $previous);
        $copy = $original->withContext(['a' => 1]);

        self::assertSame('boom', $copy->getMessage());
        self::assertSame(7, $copy->getCode());
        self::assertSame($previous, $copy->getPrevious());
        self::assertSame(['a' => 1], $copy->getRawContext());
    }

    #[Test]
    public function withContextPreservesTheInstanceRedactorForExplicitCopies(): void
    {
        $spy = new SpyRedactor();
        $copy = (new StatefulContextExceptionFixture('boom'))
            ->withRedactor($spy)
            ->withContext(['a' => 1]);

        self::assertSame(['a' => 1], $copy->getContext());
        self::assertSame(1, $spy->calls);
    }

    private static function make(): Throwable
    {
        return new RuntimeExceptionFixture();
    }
}
