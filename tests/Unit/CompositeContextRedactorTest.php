<?php

declare(strict_types=1);

namespace JOOservices\Exceptions\Tests\Unit;

use JOOservices\Exceptions\Contracts\ContextRedactorInterface;
use JOOservices\Exceptions\Support\CompositeContextRedactor;
use JOOservices\Exceptions\Support\DefaultContextRedactor;
use JOOservices\Exceptions\Tests\Fixtures\SpyRedactor;
use JOOservices\Exceptions\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class CompositeContextRedactorTest extends TestCase
{
    #[Test]
    public function masksExtraKeysRecursivelyAndCaseInsensitively(): void
    {
        $redactor = CompositeContextRedactor::withExtraKeys(['national_id', 'ssn']);

        $result = $redactor->redact([
            'national_id' => '123',
            'SSN' => '456',
            'profile' => ['national_id' => '789', 'name' => 'Viet'],
            'name' => 'Viet',
        ]);

        self::assertSame([
            'national_id' => DefaultContextRedactor::REDACTED_VALUE,
            'SSN' => DefaultContextRedactor::REDACTED_VALUE,
            'profile' => ['national_id' => DefaultContextRedactor::REDACTED_VALUE, 'name' => 'Viet'],
            'name' => 'Viet',
        ], $result);
    }

    #[Test]
    public function stillMasksDefaultSensitiveKeys(): void
    {
        $redactor = new CompositeContextRedactor();

        $result = $redactor->redact(['password' => 'hunter2', 'safe' => 1]);

        self::assertSame([
            'password' => DefaultContextRedactor::REDACTED_VALUE,
            'safe' => 1,
        ], $result);
    }

    #[Test]
    public function delegatesToInnerRedactorFirst(): void
    {
        $inner = new SpyRedactor();
        $redactor = CompositeContextRedactor::withExtraKeys(['domain_key'], $inner);

        $result = $redactor->redact(['domain_key' => 'x', 'plain' => 'y']);

        self::assertSame(1, $inner->calls);
        self::assertSame(['domain_key' => 'x', 'plain' => 'y'], $inner->lastContext);
        self::assertSame([
            'domain_key' => DefaultContextRedactor::REDACTED_VALUE,
            'plain' => 'y',
        ], $result);
    }

    #[Test]
    public function deduplicatesExtraKeys(): void
    {
        $redactor = new CompositeContextRedactor(new DefaultContextRedactor(), ['A', 'a', 'A']);

        $result = $redactor->redact(['a' => 'x']);

        self::assertSame(['a' => DefaultContextRedactor::REDACTED_VALUE], $result);
    }

    #[Test]
    public function passesNonArrayValuesThrough(): void
    {
        $redactor = new CompositeContextRedactor();

        $result = $redactor->redact(['count' => 3, 'flag' => true, 'null' => null]);

        self::assertSame(['count' => 3, 'flag' => true, 'null' => null], $result);
    }

    #[Test]
    public function capsDeepOutputFromACustomInnerRedactor(): void
    {
        $context = [];

        for ($depth = 0; $depth <= 64; $depth++) {
            $context = ['nested' => $context];
        }

        $inner = new class ($context) implements ContextRedactorInterface {
            /** @var array<string, mixed> */
            private readonly array $output;

            /** @param array<string, mixed> $output */
            public function __construct(array $output)
            {
                $this->output = $output;
            }

            /** @param array<array-key, mixed> $context @return array<string, mixed> */
            public function redact(array $context): array
            {
                return $this->output;
            }
        };

        $actual = (new CompositeContextRedactor($inner))->redact([]);

        for ($depth = 0; $depth < 64; $depth++) {
            $nested = $actual['nested'];
            self::assertIsArray($nested);
            $actual = $nested;
        }

        self::assertSame(['_context' => '[MAX_DEPTH]'], $actual);
    }
}
