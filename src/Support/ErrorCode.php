<?php

declare(strict_types=1);

namespace JOOservices\Exceptions\Support;

use LogicException;

/**
 * Stable error-code convention: {package}.{domain}.{reason}.
 *
 * Lowercase, dot-separated, ASCII. Do not encode HTTP status in the code —
 * map status at the HTTP boundary.
 */
final class ErrorCode
{
    public const GENERIC = 'exception.generic';

    /** @var non-empty-string */
    private const PATTERN = '/^[a-z0-9]+(?:\.[a-z0-9]+)+$/';

    public static function isValid(string $code): bool
    {
        return $code === self::GENERIC || preg_match(self::PATTERN, $code) === 1;
    }

    /**
     * @throws LogicException when the code violates the convention
     */
    public static function assertValid(string $code): void
    {
        if (!self::isValid($code)) {
            throw new LogicException(sprintf(
                'Invalid error code "%s". Use {package}.{domain}.{reason} (lowercase, dot-separated, ASCII).',
                $code,
            ));
        }
    }
}
