<?php

declare(strict_types=1);

namespace JOOservices\Exceptions\Contracts;

/**
 * Marker interface for all JOOservices runtime exceptions.
 *
 * Use for operational failures (timeout, network issues, resource locking)
 * that can potentially be recovered from by the caller.
 */
interface JOORuntimeExceptionInterface extends JOOExceptionInterface
{
}
