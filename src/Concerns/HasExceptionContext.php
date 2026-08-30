<?php

declare(strict_types=1);

namespace JOOservices\Exceptions\Concerns;

use JOOservices\Exceptions\Base\AbstractContextAwareException;
use JOOservices\Exceptions\Contracts\ContextRedactorInterface;
use JOOservices\Exceptions\Support\ErrorCode;
use JOOservices\Exceptions\Support\ExceptionContext;
use JOOservices\Exceptions\Support\ExceptionLogPayload;
use JOOservices\Exceptions\Support\LogLevel;
use LogicException;

/**
 * Grants context-awareness to any exception class regardless of its existing
 * inheritance chain.
 *
 * Use this trait when your package exception CANNOT extend
 * AbstractContextAwareException / AbstractContextAwareLogicException because
 * it already extends a third-party base exception.
 *
 * Requirements for the using class:
 *  1. Must implement ContextAwareExceptionInterface (and preferably LoggableExceptionInterface)
 *  2. Must call $this->initContext() in its constructor
 *  3. Must implement copyWithContext() for its own constructor invariants
 *
 * Usage:
 *
 *   class GuzzleWrappedException extends GuzzleException
 *       implements ContextAwareExceptionInterface
 *   {
 *       use HasExceptionContext;
 *
 *       public function __construct(string $message, int $code = 0, ?\Throwable $previous = null)
 *       {
 *           parent::__construct($message, $code, $previous);
 *           $this->initContext();
 *       }
 *
 *       protected function copyWithContext(ExceptionContext $context): static
 *       {
 *           $copy = new self($this->getMessage(), $this->getCode(), $this->getPrevious());
 *           $copy->initContext($context->toArray());
 *
 *           return $copy;
 *       }
 *   }
 *
 * @see ContextAwareExceptionInterface
 */
trait HasExceptionContext
{
    private ?ExceptionContext $exceptionContext = null;

    private ?ContextRedactorInterface $instanceRedactor = null;

    /**
     * Initialise the context bag. MUST be called in the using class constructor.
     *
     * @param  array<string, mixed>  $initial
     */
    protected function initContext(array $initial = []): void
    {
        $this->exceptionContext = new ExceptionContext($initial);
    }

    /**
     * Redacted diagnostic context safe for structured logs and external responses.
     *
     * @return array<string, mixed>
     *
     * @throws LogicException when initContext() was not called in the constructor
     */
    public function getContext(): array
    {
        return $this->resolveRedactor()->redact($this->getRawContext());
    }

    /**
     * Unredacted context for internal diagnostics and unit tests only.
     *
     * Never pass this array to loggers, error trackers, or API clients —
     * it may contain secrets. Prefer getContext() for any external boundary.
     *
     * @return array<string, mixed>
     *
     * @throws LogicException when initContext() was not called in the constructor
     */
    public function getRawContext(): array
    {
        return $this->requireContext()->toArray();
    }

    /**
     * @param  array<string, mixed>  $context
     *
     * @throws LogicException when initContext() was not called in the constructor
     */
    public function withContext(array $context): static
    {
        $copy = $this->copyWithContext($this->requireContext()->merge($context));
        $copy->instanceRedactor = $this->instanceRedactor;

        return $copy;
    }

    /**
     * Returns a new instance bound to a specific redactor.
     *
     * Per-instance overrides fall back to the global registry. Use for
     * long-lived workers (Swoole / RoadRunner) or tests where the process-wide
     * static redactor is inappropriate.
     *
     * @throws LogicException when initContext() was not called in the constructor
     */
    public function withRedactor(ContextRedactorInterface $redactor): static
    {
        $copy = $this->copyWithContext($this->requireContext());
        $copy->instanceRedactor = $redactor;

        return $copy;
    }

    public function errorCode(): string
    {
        return ErrorCode::GENERIC;
    }

    public function logLevel(): string
    {
        return LogLevel::ERROR->value;
    }

    /**
     * @return array<string, mixed>
     *
     * @throws LogicException when initContext() was not called in the constructor
     */
    public function toLogArray(): array
    {
        return (new ExceptionLogPayload())->build(
            $this,
            $this->errorCode(),
            $this->logLevel(),
            $this->getContext(),
        );
    }

    /**
     * Template method: every concrete class must reconstruct its own
     * constructor invariants and pass the merged ExceptionContext.
     */
    abstract protected function copyWithContext(ExceptionContext $context): static;

    private function resolveRedactor(): ContextRedactorInterface
    {
        return $this->instanceRedactor ?? AbstractContextAwareException::getRedactor();
    }

    /**
     * @throws LogicException when initContext() was not called in the constructor
     */
    private function requireContext(): ExceptionContext
    {
        if ($this->exceptionContext === null) {
            throw new LogicException(sprintf(
                '%s must call initContext() in its constructor before using context methods.',
                static::class,
            ));
        }

        return $this->exceptionContext;
    }
}
