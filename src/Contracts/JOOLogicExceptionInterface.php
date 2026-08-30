<?php

declare(strict_types=1);

namespace JOOservices\Exceptions\Contracts;

/**
 * Marker interface for all JOOservices logic exceptions.
 *
 * Use for programmer errors (invalid arguments, configuration mismatch,
 * domain invariant violations) that must be fixed by correcting the calling
 * code rather than recovered from at runtime.
 */
interface JOOLogicExceptionInterface extends JOOExceptionInterface
{
}
