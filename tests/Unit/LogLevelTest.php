<?php

declare(strict_types=1);

namespace JOOservices\Exceptions\Tests\Unit;

use JOOservices\Exceptions\Support\LogLevel;
use JOOservices\Exceptions\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

final class LogLevelTest extends TestCase
{
    #[Test]
    public function exposesTheFullPsr3Vocabulary(): void
    {
        self::assertSame(
            ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'],
            LogLevel::all(),
        );
    }

    #[Test]
    public function roundTripsValues(): void
    {
        foreach (LogLevel::cases() as $level) {
            self::assertSame($level, LogLevel::tryFrom($level->value));
        }
    }

    #[Test]
    public function rejectsUnknownValues(): void
    {
        self::assertNull(LogLevel::tryFrom('warn'));
        self::assertNull(LogLevel::tryFrom(''));
    }
}
