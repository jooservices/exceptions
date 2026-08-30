<?php

declare(strict_types=1);

namespace JOOservices\Exceptions\Base;

use JOOservices\Exceptions\Concerns\ProvidesStructuredContext;
use JOOservices\Exceptions\Contracts\ContextAwareExceptionInterface;
use JOOservices\Exceptions\Contracts\ContextRedactorInterface;
use JOOservices\Exceptions\Contracts\LoggableExceptionInterface;
use JOOservices\Exceptions\Support\CompositeContextRedactor;
use JOOservices\Exceptions\Support\DefaultContextRedactor;
use JOOservices\Exceptions\Support\ExceptionContext;
use Throwable;

/**
 * Runtime exception base with pluggable structured context and secret redaction.
 *
 * Extend this class when your package exception must carry diagnostic context
 * that is safe to log (entity IDs, field paths, type names, etc.) but must not
 * expose secrets (tokens, passwords, raw payloads).
 *
 * How context flows:
 *   1. Caller creates an exception and chains context via withContext()
 *   2. On getContext(), the raw ExceptionContext is passed through the
 *      registered ContextRedactorInterface or package-safe default before being returned
 *   3. Logging and error reporting consumers receive the sanitised array
 *
 * Redactor registration (once at bootstrap, application-level).
 * Prefer CompositeContextRedactor::withExtraKeys() or decorate DefaultContextRedactor:
 *
 *   AbstractContextAwareException::setRedactor(
 *       CompositeContextRedactor::withExtraKeys(['national_id', 'ssn'])
 *   );
 *
 * Typical subclass usage:
 *
 *   final class HydrationException extends AbstractContextAwareException
 *   {
 *       public static function forField(string $path, string $expectedType): self
 *       {
 *           return (new self("Hydration failed for field '{$path}'"))
 *               ->withContext([
 *                   'path'         => $path,
 *                   'expectedType' => $expectedType,
 *               ]);
 *       }
 *
 *       protected function copyWithContext(ExceptionContext $context): static
 *       {
 *           return new self($this->getMessage(), $this->getCode(), $this->getPrevious(), $context);
 *       }
 *   }
 *
 * @phpstan-consistent-constructor
 */
abstract class AbstractContextAwareException extends AbstractJOORuntimeException implements
    ContextAwareExceptionInterface,
    LoggableExceptionInterface
{
    use ProvidesStructuredContext;

    /** Package-wide optional application redactor. */
    private static ?ContextRedactorInterface $redactor = null;

    /** Shared stateless fallback to avoid allocating a redactor for every read. */
    private static ?ContextRedactorInterface $defaultRedactor = null;

    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        ?ExceptionContext $context = null,
    ) {
        parent::__construct($message, $code, $previous);
        $this->context = $context ?? new ExceptionContext();
    }

    /**
     * Registers a global redactor applied to all instances of this class and
     * any subclass. Call this once in your application service provider or
     * bootstrap file.
     */
    public static function setRedactor(ContextRedactorInterface $redactor): void
    {
        self::$redactor = $redactor;
    }

    /**
     * Removes the application override and restores the package-safe default.
     */
    public static function removeRedactor(): void
    {
        self::$redactor = null;
    }

    /**
     * Returns the application redactor or the package's safe default.
     */
    public static function getRedactor(): ContextRedactorInterface
    {
        return self::$redactor ?? (self::$defaultRedactor ??= new DefaultContextRedactor());
    }
}
