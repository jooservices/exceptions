<?php

declare(strict_types=1);

namespace JOOservices\Exceptions\Contracts;

/**
 * Contract for exceptions that expose stable structured logging metadata.
 */
interface LoggableExceptionInterface extends JOOExceptionInterface
{
    /**
     * Stable machine-readable error code following {package}.{domain}.{reason}.
     */
    public function errorCode(): string;

    /**
     * PSR-3 log level vocabulary (see \JOOservices\Exceptions\Support\LogLevel).
     */
    public function logLevel(): string;

    /**
     * Structured, redacted log payload.
     *
     * @return array<string, mixed>
     */
    public function toLogArray(): array;
}
