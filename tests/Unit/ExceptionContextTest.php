<?php

declare(strict_types=1);

namespace JOOservices\Exceptions\Tests\Unit;

use JOOservices\Exceptions\Support\ExceptionContext;
use JOOservices\Exceptions\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class ExceptionContextTest extends TestCase
{
    #[Test]
    public function preservesIntegerKeys(): void
    {
        $context = new ExceptionContext([0 => 'zero', 5 => 'five', 'named' => 'value']);

        self::assertSame(['0' => 'zero', '5' => 'five', 'named' => 'value'], $context->toArray());
    }

    #[Test]
    public function mergeReturnsNewInstanceWithLaterKeysWinning(): void
    {
        $original = new ExceptionContext(['a' => 1, 'b' => 2]);
        $merged = $original->merge(['b' => 20, 'c' => 30]);

        self::assertNotSame($original, $merged);
        self::assertSame(['a' => 1, 'b' => 2], $original->toArray());
        self::assertSame(['a' => 1, 'b' => 20, 'c' => 30], $merged->toArray());
    }

    #[Test]
    public function mergePreservesIntegerKeysWithoutRenumbering(): void
    {
        $merged = (new ExceptionContext())->merge([7 => 'seven', 8 => 'eight']);

        self::assertSame(['7' => 'seven', '8' => 'eight'], $merged->toArray());
    }

    #[Test]
    public function detectsEmptyContext(): void
    {
        self::assertTrue((new ExceptionContext())->isEmpty());
        self::assertFalse((new ExceptionContext(['a' => 1]))->isEmpty());
    }

    #[Test]
    public function reportsKeyPresence(): void
    {
        $context = new ExceptionContext(['a' => null]);

        self::assertTrue($context->has('a'));
        self::assertFalse($context->has('b'));
    }

    #[Test]
    public function readsValuesWithDefault(): void
    {
        $context = new ExceptionContext(['a' => 'value']);

        self::assertSame('value', $context->get('a'));
        self::assertSame('fallback', $context->get('missing', 'fallback'));
        self::assertNull($context->get('missing'));
    }
}
