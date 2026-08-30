<?php

declare(strict_types=1);

namespace JOOservices\Exceptions\Contracts;

/**
 * Strategy for sanitising exception context before external exposure.
 *
 * Implementations are responsible for masking or removing any keys whose
 * values must not appear in logs, error responses, or traces (e.g. passwords,
 * tokens, secrets, PII).
 *
 * Register a global redactor once at application bootstrap:
 *
 *   AbstractContextAwareException::setRedactor(new MyRedactor());
 *
 * The redactor is called by AbstractContextAwareException::getContext()
 * before the context array is returned. If no application redactor is
 * registered, the package-safe default redactor is used.
 *
 * @see ContextAwareExceptionInterface::getContext()
 * @see \JOOservices\Exceptions\Base\AbstractContextAwareException::setRedactor()
 */
interface ContextRedactorInterface
{
    /**
     * Sanitises the given context array.
     *
     * Sensitive keys MUST be replaced with a safe placeholder
     * (e.g. '[REDACTED]') rather than removed entirely, so log consumers
     * know a field existed without seeing its value.
     *
     * @param  array<array-key, mixed>  $context
     * @return array<string, mixed>
     */
    public function redact(array $context): array;
}
