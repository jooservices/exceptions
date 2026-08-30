<?php

declare(strict_types=1);

namespace JOOservices\Exceptions\Tests\Unit;

use JOOservices\Exceptions\Support\ErrorCode;
use JOOservices\Exceptions\Tests\TestCase;
use LogicException;
use PHPUnit\Framework\Attributes\Test;

final class ErrorCodeTest extends TestCase
{
    #[Test]
    public function acceptsTheGenericCode(): void
    {
        self::assertTrue(ErrorCode::isValid(ErrorCode::GENERIC));
    }

    #[Test]
    public function acceptsDotSeparatedLowercaseSlugs(): void
    {
        self::assertTrue(ErrorCode::isValid('dto.hydration.failed'));
        self::assertTrue(ErrorCode::isValid('client.http.timeout'));
        self::assertTrue(ErrorCode::isValid('auth.token.expired'));
    }

    #[Test]
    public function rejectsMalformedCodes(): void
    {
        self::assertFalse(ErrorCode::isValid('nodots'));
        self::assertFalse(ErrorCode::isValid('Invalid.Code'));
        self::assertFalse(ErrorCode::isValid('dto..failed'));
        self::assertFalse(ErrorCode::isValid('.leading.dot'));
        self::assertFalse(ErrorCode::isValid('trailing.dot.'));
        self::assertFalse(ErrorCode::isValid(''));
    }

    /**
     * @throws LogicException
     */
    #[Test]
    public function assertValidThrowsForMalformedCodes(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Invalid error code');

        ErrorCode::assertValid('Bad Code!');
    }

    /**
     * @throws LogicException
     */
    #[Test]
    public function assertValidPassesForWellFormedCodes(): void
    {
        ErrorCode::assertValid('exceptions.test.ok');

        $this->addToAssertionCount(1);
    }
}
