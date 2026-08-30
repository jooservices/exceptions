<?php

declare(strict_types=1);

namespace JOOservices\Exceptions\Contracts;

/**
 * Contract for exceptions that carry structured diagnostic context.
 *
 * Context MUST be safe to log and safe to expose in structured error
 * responses. Implementations MUST sanitise context through a
 * ContextRedactorInterface before exposing values externally. Never store
 * credentials, tokens, or PII directly in context arrays.
 *
 * Usage:
 *
 *   $e = new SomeException('lookup failed')
 *       ->withContext(['entity' => 'user', 'id' => $userId]);
 *
 *   $logger->error($e->getMessage(), $e->getContext());
 *
 * @see ContextRedactorInterface for the secret-masking strategy
 * @see \JOOservices\Exceptions\Base\AbstractContextAwareException for the base implementation
 */
interface ContextAwareExceptionInterface extends JOOExceptionInterface
{
    /**
     * Returns sanitised diagnostic context suitable for structured logging.
     *
     * The returned array MUST have been processed through a registered
     * ContextRedactorInterface before being returned.
     *
     * @return array<string, mixed>
     */
    public function getContext(): array;

    /**
     * Returns a new instance augmented with additional context entries.
     *
     * The original instance MUST remain unmodified — exceptions are treated
     * as value objects in this package.
     *
     * @param  array<string, mixed>  $context
     */
    public function withContext(array $context): static;
}
