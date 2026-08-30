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
 * Shared context + loggable behaviour for the package abstract context bases.
 *
 * @internal Used by AbstractContextAwareException and AbstractContextAwareLogicException.
 */
trait ProvidesStructuredContext
{
    private ExceptionContext $context;

    private ?ContextRedactorInterface $instanceRedactor = null;

    /**
     * Redacted diagnostic context safe for structured logs and external responses.
     *
     * @return array<string, mixed>
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
     */
    public function getRawContext(): array
    {
        return $this->context->toArray();
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function withContext(array $context): static
    {
        $copy = $this->copyWithContext($this->context->merge($context));
        $copy->instanceRedactor = $this->instanceRedactor;

        return $copy;
    }

    /**
     * Returns a new instance bound to a specific redactor.
     *
     * Per-instance overrides fall back to the global registry. Use for
     * long-lived workers (Swoole / RoadRunner) or tests where the process-wide
     * static redactor is inappropriate.
     */
    public function withRedactor(ContextRedactorInterface $redactor): static
    {
        $copy = $this->copyWithContext($this->context);
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
     * @throws LogicException when errorCode()/logLevel() violate the payload contract
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
     * Template method: every concrete subclass must reconstruct its own
     * constructor invariants and pass the merged ExceptionContext.
     */
    abstract protected function copyWithContext(ExceptionContext $context): static;

    private function resolveRedactor(): ContextRedactorInterface
    {
        return $this->instanceRedactor ?? AbstractContextAwareException::getRedactor();
    }
}
