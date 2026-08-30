<?php

declare(strict_types=1);

namespace JOOservices\Exceptions\Support;

/**
 * PSR-3 log level vocabulary without a psr/log dependency.
 *
 * A string-backed enum so logLevel() implementations get autocomplete and
 * fail-fast validation instead of drifting between "warn" and "warning".
 * The public contract stays logLevel(): string — return ->value.
 */
enum LogLevel: string
{
    case DEBUG = 'debug';
    case INFO = 'info';
    case NOTICE = 'notice';
    case WARNING = 'warning';
    case ERROR = 'error';
    case CRITICAL = 'critical';
    case ALERT = 'alert';
    case EMERGENCY = 'emergency';

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return array_map(
            static fn(LogLevel $level): string => $level->value,
            self::cases(),
        );
    }
}
