<?php

declare(strict_types=1);

namespace JOOservices\Exceptions\Tests\Unit;

use JOOservices\Exceptions\Contracts\ContextAwareExceptionInterface;
use JOOservices\Exceptions\Contracts\LoggableExceptionInterface;
use JOOservices\Exceptions\Support\DefaultContextRedactor;
use JOOservices\Exceptions\Tests\Fixtures\SpyRedactor;
use JOOservices\Exceptions\Tests\Fixtures\TraitBasedExceptionFixture;
use JOOservices\Exceptions\Tests\Fixtures\UninitializedTraitExceptionFixture;
use JOOservices\Exceptions\Tests\TestCase;
use LogicException;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Throwable;

final class HasExceptionContextTest extends TestCase
{
    #[Test]
    public function implementsTheContextContractOnAThirdPartyParent(): void
    {
        $exception = self::make();

        self::assertInstanceOf(ContextAwareExceptionInterface::class, $exception);
        self::assertInstanceOf(LoggableExceptionInterface::class, $exception);
    }

    /**
     * @throws LogicException
     */
    #[Test]
    public function startsWithEmptyContext(): void
    {
        $exception = new TraitBasedExceptionFixture('boom');

        self::assertSame([], $exception->getRawContext());
        self::assertSame([], $exception->getContext());
    }

    /**
     * @throws LogicException
     */
    #[Test]
    public function withContextReturnsNewImmutableInstance(): void
    {
        $original = new TraitBasedExceptionFixture('boom');
        $augmented = $original->withContext(['a' => 1]);

        self::assertNotSame($original, $augmented);
        self::assertSame([], $original->getRawContext());
        self::assertSame(['a' => 1], $augmented->getRawContext());
    }

    /**
     * @throws LogicException
     */
    #[Test]
    public function getContextRedactsSensitiveKeys(): void
    {
        $exception = (new TraitBasedExceptionFixture('boom'))->withContext([
            'token' => 'abc123',
            'safe' => 'kept',
        ]);

        self::assertSame([
            'token' => DefaultContextRedactor::REDACTED_VALUE,
            'safe' => 'kept',
        ], $exception->getContext());
    }

    #[Test]
    public function exposesDefaultMetadata(): void
    {
        $exception = new TraitBasedExceptionFixture();

        self::assertSame('exceptions.test.trait', $exception->errorCode());
        self::assertSame('error', $exception->logLevel());
    }

    #[Test]
    public function fallsBackToGenericMetadataWithoutOverrides(): void
    {
        $exception = new class extends RuntimeException implements ContextAwareExceptionInterface, LoggableExceptionInterface {
            use \JOOservices\Exceptions\Concerns\HasExceptionContext;

            public function __construct()
            {
                parent::__construct();
                $this->initContext();
            }

            protected function copyWithContext(\JOOservices\Exceptions\Support\ExceptionContext $context): static
            {
                $copy = new self();
                $copy->initContext($context->toArray());

                return $copy;
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
        $exception = new TraitBasedExceptionFixture('boom', 3);

        $payload = $exception->toLogArray();

        self::assertSame('boom', $payload['message']);
        self::assertSame(TraitBasedExceptionFixture::class, $payload['class']);
        self::assertSame('exceptions.test.trait', $payload['error_code']);
        self::assertSame([], $payload['context']);
    }

    /**
     * @throws LogicException
     */
    #[Test]
    public function withRedactorOverridesTheGlobalRegistry(): void
    {
        $spy = new SpyRedactor();

        $scoped = (new TraitBasedExceptionFixture('boom'))->withRedactor($spy);

        self::assertSame([], $scoped->getContext());
        self::assertSame(1, $spy->calls);
    }

    /**
     * @throws LogicException
     */
    #[Test]
    public function withContextPreservesTheInstanceRedactor(): void
    {
        $spy = new SpyRedactor();
        $copy = (new TraitBasedExceptionFixture('boom'))
            ->withRedactor($spy)
            ->withContext(['a' => 1]);

        self::assertSame(['a' => 1], $copy->getContext());
        self::assertSame(1, $spy->calls);
    }

    /**
     * @throws LogicException
     */
    #[Test]
    public function failsFastWhenInitContextWasNotCalled(): void
    {
        $exception = new UninitializedTraitExceptionFixture('boom');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('must call initContext()');

        $exception->getContext();
    }

    /**
     * @throws LogicException
     */
    #[Test]
    public function failsFastOnWithContextWhenInitContextWasNotCalled(): void
    {
        $exception = new UninitializedTraitExceptionFixture('boom');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('must call initContext()');

        $exception->withContext(['a' => 1]);
    }

    private static function make(): Throwable
    {
        return new TraitBasedExceptionFixture('boom');
    }
}
