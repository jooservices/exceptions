<?php

declare(strict_types=1);

namespace JOOservices\Exceptions\Contracts;

use Throwable;

/**
 * Root marker interface for all JOOservices exceptions.
 *
 * Implementing this interface is the ONLY requirement for an exception to be
 * recognised as a JOOservices exception. It carries no methods so existing
 * exception hierarchies can adopt it without breaking call sites.
 *
 * Usage:
 *
 *   catch (JOOExceptionInterface $e) { ... }
 *
 * @see ContextAwareExceptionInterface for exceptions that carry diagnostic context
 */
interface JOOExceptionInterface extends Throwable
{
}
