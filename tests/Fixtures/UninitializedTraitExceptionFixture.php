<?php

declare(strict_types=1);

namespace JOOservices\Exceptions\Tests\Fixtures;

use JOOservices\Exceptions\Concerns\HasExceptionContext;
use JOOservices\Exceptions\Contracts\ContextAwareExceptionInterface;
use JOOservices\Exceptions\Support\ExceptionContext;
use LogicException;
use RuntimeException;

/**
 * Deliberately broken trait consumer: never calls initContext().
 */
final class UninitializedTraitExceptionFixture extends RuntimeException implements ContextAwareExceptionInterface
{
    use HasExceptionContext;

    /**
     * @throws LogicException never in practice — requireContext() throws first
     */
    protected function copyWithContext(ExceptionContext $context): static
    {
        throw new LogicException('Not reachable in tests.');
    }
}
